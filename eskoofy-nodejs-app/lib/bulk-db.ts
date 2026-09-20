import { hashPassword } from "@/lib/auth";
import { buildCsv, exportFilename, toYmd, type BulkResource } from "@/lib/bulk";
import { prisma } from "@/lib/prisma";

/**
 * Prisma-backed bulk export/import, mirroring the Laravel app's
 * `DashboardBulkController`. The CSV surface is the shared contract (lib/bulk.ts),
 * so exported files round-trip across every variant.
 */

function engine(): "mysql" | "sqlite" {
  return (process.env.DATABASE_URL ?? "").toLowerCase().startsWith("sqlite") ? "sqlite" : "mysql";
}

/** Parse `YYYY-MM-DD` (or `YYYY-MM-DD HH:MM:SS`) into a midday Date. */
function parseDate(value: string | null | undefined): Date | null {
  if (!value) return null;
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim());
  if (!match) return null;
  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0, 0);
}

async function studentRoleId(): Promise<number | undefined> {
  return prisma.roles.findUnique({ where: { name: "student" } }).then((role) => role?.id);
}

async function teacherRoleId(): Promise<number | undefined> {
  return prisma.roles.findUnique({ where: { name: "teacher" } }).then((role) => role?.id);
}

// ── Export ───────────────────────────────────────────────────────────────────

export async function exportCsv(resource: BulkResource): Promise<{ filename: string; text: string }> {
  const engineName = engine();
  let rows: Array<Record<string, unknown>> = [];

  if (resource === "students") {
    const students = await prisma.students.findMany({
      orderBy: { id: "asc" },
      include: { users: true, school_classes: true },
    });
    rows = students.map((s) => ({
      name: s.users?.name ?? "",
      email: s.users?.email ?? "",
      admission_number: s.admission_number,
      admission_date: toYmd(s.admission_date, engineName),
      class_code: s.school_classes?.code ?? "",
      roll_number: s.roll_number ?? "",
      gender: s.gender ?? "",
      date_of_birth: toYmd(s.date_of_birth, engineName),
      phone: s.phone ?? "",
      present_address: s.present_address ?? "",
      status: s.status ?? "",
    }));
  } else if (resource === "teachers") {
    const teachers = await prisma.teachers.findMany({
      orderBy: { id: "asc" },
      include: { users: true },
    });
    rows = teachers.map((t) => ({
      name: t.users?.name ?? "",
      email: t.users?.email ?? "",
      employee_id: t.employee_id ?? "",
      joining_date: toYmd(t.joining_date, engineName),
      phone: t.phone ?? "",
      gender: t.gender ?? "",
      date_of_birth: toYmd(t.date_of_birth, engineName),
      qualification: t.qualification ?? "",
      specialization: "",
      status: t.status ?? "",
    }));
  } else if (resource === "fees") {
    const fees = await prisma.fees.findMany({
      orderBy: { id: "asc" },
      include: { students: { include: { users: true } } },
    });
    rows = fees.map((f) => ({
      student: f.students?.users?.name ?? "",
      fee: f.name,
      amount: String(f.amount),
      due_date: toYmd(f.start_date, engineName),
      status: f.status ?? "",
      paid_at: "",
    }));
  } else {
    const attendances = await prisma.attendances.findMany({
      orderBy: { date: "desc" },
      take: 10000,
      include: { students: { include: { users: true } }, school_classes: true },
    });
    rows = attendances.map((a) => ({
      date: toYmd(a.date, engineName),
      student: a.students?.users?.name ?? "",
      class: a.school_classes?.name ?? "",
      status: a.status ?? "",
      remarks: a.remarks ?? "",
    }));
  }

  return { filename: exportFilename(resource), text: buildCsv(resource, rows) };
}

// ── Import ───────────────────────────────────────────────────────────────────

/** Upsert a CSV student row — mirrors `DashboardBulkController::importStudent`. */
export async function upsertStudentRow(row: Record<string, string | null>): Promise<"created" | "updated" | "skipped"> {
  const email = (row.email ?? "").trim();
  const admission = (row.admission_number ?? "").trim();
  const name = (row.name ?? "").trim();
  if (!email || !admission || !name) return "skipped";

  const classCode = (row.class_code ?? "").trim();
  let classId: number | null = null;
  if (classCode) {
    const schoolClass = await prisma.school_classes.findFirst({ where: { code: classCode } });
    if (!schoolClass) throw new Error(`class_code '${classCode}' not found`);
    classId = schoolClass.id;
  }
  if (classId === null) throw new Error(`class_code '${classCode}' not found`);

  let user = await prisma.users.findUnique({ where: { email } });
  let created = false;
  if (!user) {
    user = await prisma.users.create({
      data: {
        name,
        email,
        email_verified_at: new Date(),
        role_id: (await studentRoleId()) ?? 1,
        role: "student",
        password: await hashPassword((row.password ?? "password") as string),
      },
    });
    created = true;
  }

  const existing = await prisma.students.findUnique({ where: { admission_number: admission } });
  const nameParts = name.split(" ");
  const firstName = nameParts[0];
  const lastName = nameParts.slice(1).join(" ") || firstName;

  await prisma.students.upsert({
    where: { admission_number: admission },
    update: {
      user_id: user.id,
      class_id: classId,
      first_name: firstName,
      last_name: lastName,
      admission_date: parseDate(row.admission_date) ?? new Date(),
      roll_number: row.roll_number,
      gender: row.gender,
      date_of_birth: parseDate(row.date_of_birth),
      phone: row.phone,
      present_address: row.present_address,
      status: row.status ?? "active",
      nationality: "Bangladeshi",
      country: "Bangladesh",
    },
    create: {
      user_id: user.id,
      class_id: classId,
      admission_number: admission,
      first_name: firstName,
      last_name: lastName,
      admission_date: parseDate(row.admission_date) ?? new Date(),
      roll_number: row.roll_number,
      gender: row.gender,
      date_of_birth: parseDate(row.date_of_birth),
      phone: row.phone,
      present_address: row.present_address,
      status: row.status ?? "active",
      nationality: "Bangladeshi",
      country: "Bangladesh",
    },
  });

  return existing ? "updated" : created ? "created" : "updated";
}

/** Upsert a CSV teacher row — mirrors `DashboardBulkController::importTeacher`. */
export async function upsertTeacherRow(row: Record<string, string | null>): Promise<"created" | "updated" | "skipped"> {
  const email = (row.email ?? "").trim();
  const employeeId = (row.employee_id ?? "").trim();
  const name = (row.name ?? "").trim();
  if (!email || !employeeId || !name) return "skipped";

  let user = await prisma.users.findUnique({ where: { email } });
  let created = false;
  if (!user) {
    user = await prisma.users.create({
      data: {
        name,
        email,
        email_verified_at: new Date(),
        role_id: (await teacherRoleId()) ?? 1,
        role: "teacher",
        password: await hashPassword((row.password ?? "password") as string),
      },
    });
    created = true;
  }

  const existing = await prisma.teachers.findUnique({ where: { employee_id: employeeId } });
  await prisma.teachers.upsert({
    where: { employee_id: employeeId },
    update: {
      user_id: user.id,
      phone: row.phone,
      gender: row.gender,
      date_of_birth: parseDate(row.date_of_birth),
      joining_date: parseDate(row.joining_date) ?? new Date(),
      qualification: row.qualification,
      status: row.status ?? "active",
    },
    create: {
      user_id: user.id,
      phone: row.phone,
      gender: row.gender,
      date_of_birth: parseDate(row.date_of_birth),
      joining_date: parseDate(row.joining_date) ?? new Date(),
      qualification: row.qualification,
      status: row.status ?? "active",
    },
  });

  return existing ? "updated" : created ? "created" : "updated";
}

export const IMPORT_UPSERTERS: Record<"students" | "teachers", (row: Record<string, string | null>) => Promise<"created" | "updated" | "skipped">> = {
  students: upsertStudentRow,
  teachers: upsertTeacherRow,
};