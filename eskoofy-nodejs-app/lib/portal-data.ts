import { prisma } from "@/lib/prisma";

/** Row shape for arbitrary Prisma rows (keeps typecheck simple). */
type Row = Record<string, unknown>;

export interface PortalChild {
  id: number;
  name: string;
  className: string | null;
  sectionName: string | null;
  roll: string | null;
}

export interface PortalPayload {
  user: { id: number; name: string; email: string; role: string };
  isStudent: boolean;
  isParent: boolean;
  student: Row | null;
  children: PortalChild[];
  assignments: Row[];
  recentAttendance: Row[];
  examResults: Row[];
  feePayments: Row[];
  announcements: Row[];
  upcomingEvents: Row[];
  routine: Map<number, Row[]>;
  teachers: Row[];
  attendanceCalendar: Map<string, Row[]>;
  duesTimeline: Row[];
}

const fmtTime = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
};

const fmtDate = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
};

const fmtDay = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  return Number.isNaN(d.getTime()) ? "" : String(d.getDate()).padStart(2, "0");
};

const fmtMonth = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  return Number.isNaN(d.getTime()) ? "" : d.toLocaleDateString("en-US", { month: "short" });
};

const fmtTimeHM = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

const DAY_NAMES = ["", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

export async function loadPortalData(userId: number, role: string, userEmail: string, userName: string): Promise<PortalPayload> {
  const isStudent = role === "student";
  const isParent = role === "parent";

  try {
    const [upcomingEvents, announcements] = await Promise.all([
      prisma.events.findMany({
        where: { status: "published", deleted_at: null, start_date: { gte: new Date() } },
        orderBy: { start_date: "asc" },
        take: 10,
      }),
      prisma.announcements.findMany({
        where: {
          is_published: true,
          OR: [{ audience: { contains: '"all"' } }, { audience: { contains: `"${isParent ? "parent" : "student"}"` } }],
        },
        orderBy: [{ starts_at: "desc" }, { id: "desc" }],
        take: 10,
      }),
    ]);

  let student: Row | null = null;
  let children: PortalChild[] = [];
  let assignments: Row[] = [];
  let recentAttendance: Row[] = [];
  let examResults: Row[] = [];
  let feePayments: Row[] = [];
  let routine = new Map<number, Row[]>();
  let teachers: Row[] = [];
  let attendanceCalendar = new Map<string, Row[]>();
  let duesTimeline: Row[] = [];

  if (isStudent) {
    student = (await prisma.students.findFirst({
      where: { user_id: userId, deleted_at: null },
      include: { school_classes: true, sections: true, batches: true },
    })) as unknown as Row | null;

    if (student) {
      const studentId = Number(student.id);
      const batchId = Number(student.batch_id ?? 0);
      const classId = Number(student.class_id ?? 0);
      const [assign, att, results, fees, rout] = await Promise.all([
        batchId
          ? prisma.assignments.findMany({ where: { batch_id: batchId, deleted_at: null }, orderBy: { due_date: "desc" }, take: 15 })
          : Promise.resolve([]),
        prisma.attendances.findMany({ where: { student_id: studentId, deleted_at: null }, orderBy: { date: "desc" }, take: 14 }),
        prisma.exam_results.findMany({
          where: { student_id: studentId, is_published: true },
          include: { exams: true },
          orderBy: { id: "desc" },
          take: 10,
        }),
        prisma.fee_payments.findMany({ where: { student_id: studentId }, orderBy: { payment_date: "desc" }, take: 15 }),
        classId
          ? prisma.routines.findMany({
              where: { school_class_id: classId, is_active: true },
              include: { subjects: true, teachers: { include: { users: true } } },
              orderBy: [{ day_of_week: "asc" }, { start_time: "asc" }],
            })
          : Promise.resolve([]),
      ]);
      assignments = assign as unknown as Row[];
      recentAttendance = att as unknown as Row[];
      examResults = results as unknown as Row[];
      feePayments = fees as unknown as Row[];
      routine = groupRoutine(rout as unknown as Row[]);
      teachers = await teachersForClasses([classId]);
      attendanceCalendar = await attendanceCalendarFor([studentId]);
    }
  }

  if (isParent) {
    const guardian = await prisma.guardians.findFirst({ where: { user_id: userId, deleted_at: null } });
    if (guardian) {
      const links = await prisma.guardian_student.findMany({
        where: { guardian_id: guardian.id },
        include: { students: { include: { school_classes: true, sections: true, batches: true, users: true } } },
      });
      children = links.map((link) => {
        const s = link.students as unknown as Row;
        return {
          id: Number(s.id ?? 0),
          name: String((s.users as unknown as Row | undefined)?.name ?? (String(`${s.first_name ?? ""} ${s.last_name ?? ""}`).trim() || "Student")),
          className: String((s.school_classes as unknown as Row | undefined)?.name ?? null),
          sectionName: String((s.sections as unknown as Row | undefined)?.name ?? null),
          roll: String(s.roll_number ?? s.roll_no ?? null),
        };
      });
      const ids = children.map((c) => c.id);
      if (ids.length > 0) {
        const [att, results, fees] = await Promise.all([
          prisma.attendances.findMany({ where: { student_id: { in: ids }, deleted_at: null }, orderBy: { date: "desc" }, take: 20 }),
          prisma.exam_results.findMany({
            where: { student_id: { in: ids }, is_published: true },
            include: { exams: true },
            orderBy: { id: "desc" },
            take: 15,
          }),
          prisma.fee_payments.findMany({ where: { student_id: { in: ids } }, orderBy: { payment_date: "desc" }, take: 20 }),
        ]);
        recentAttendance = att as unknown as Row[];
        examResults = results as unknown as Row[];
        feePayments = fees as unknown as Row[];
        attendanceCalendar = await attendanceCalendarFor(ids);
        const childRows = links.map((l) => l.students as unknown as Row);
        const classIds = childRows.map((c) => Number(c.class_id ?? 0)).filter((v) => v > 0);
        teachers = await teachersForClasses(classIds);
      }
    }
  }

  duesTimeline = [...feePayments].sort((a, b) => {
    const da = new Date(String((a.payment_date ?? a.created_at) ?? 0)).getTime();
    const db = new Date(String((b.payment_date ?? b.created_at) ?? 0)).getTime();
    return db - da;
  });

  return {
    user: { id: userId, name: userName, email: userEmail, role },
    isStudent,
    isParent,
    student,
    children,
    assignments,
    recentAttendance,
    examResults,
    feePayments,
    announcements,
    upcomingEvents,
    routine,
    teachers,
    attendanceCalendar,
    duesTimeline,
  };
  } catch {
    return {
      user: { id: userId, name: userName, email: userEmail, role },
      isStudent,
      isParent,
      student: null,
      children: [],
      assignments: [],
      recentAttendance: [],
      examResults: [],
      feePayments: [],
      announcements: [],
      upcomingEvents: [],
      routine: new Map<number, Row[]>(),
      teachers: [],
      attendanceCalendar: new Map<string, Row[]>(),
      duesTimeline: [],
    };
  }
}

function groupRoutine(rows: Row[]): Map<number, Row[]> {
  const map = new Map<number, Row[]>();
  for (const row of rows) {
    const day = Number(row.day_of_week ?? 0);
    if (!map.has(day)) map.set(day, []);
    map.get(day)!.push(row);
  }
  return map;
}

async function teachersForClasses(classIds: number[]): Promise<Row[]> {
  const ids = [...new Set(classIds)].filter((v) => v > 0);
  if (ids.length === 0) return [];
  try {
    const links = await prisma.class_teacher.findMany({ where: { class_id: { in: ids } }, select: { teacher_id: true } });
    const teacherIds = [...new Set(links.map((l) => l.teacher_id))];
    if (teacherIds.length === 0) return [];
    const teachers = await prisma.teachers.findMany({ where: { id: { in: teacherIds }, deleted_at: null }, include: { users: true } });
    return teachers as unknown as Row[];
  } catch {
    return [];
  }
}

async function attendanceCalendarFor(studentIds: number[]): Promise<Map<string, Row[]>> {
  if (studentIds.length === 0) return new Map();
  const cutoff = new Date(Date.now() - 30 * 86_400_000);
  try {
    const rows = await prisma.attendances.findMany({
      where: { student_id: { in: studentIds }, deleted_at: null, date: { gte: cutoff } },
      orderBy: { date: "desc" },
    });
    const map = new Map<string, Row[]>();
    for (const row of rows as unknown as Row[]) {
      const key = fmtDate(row.date);
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(row);
    }
    return map;
  } catch {
    return new Map();
  }
}

export const portalFormat = { fmtDate, fmtDay, fmtMonth, fmtTime, fmtTimeHM, DAY_NAMES };

/** Mirrors Admission::PAYMENT_METHODS + status badge coloring. */
export const ATTENDANCE_BADGE = (status: unknown) => {
  const s = String(status ?? "");
  return s === "present"
    ? "bg-green-100 text-green-800"
    : s === "absent"
      ? "bg-red-100 text-red-800"
      : "bg-yellow-100 text-yellow-800";
};

export const ATTENDANCE_DOT = (status: unknown) => {
  const s = String(status ?? "");
  return s === "present" ? "bg-green-500" : s === "absent" ? "bg-red-500" : "bg-yellow-500";
};