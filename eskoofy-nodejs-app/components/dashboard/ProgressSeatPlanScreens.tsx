import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { getSiteSettings } from "@/lib/site-settings";

/**
 * Progress reports + seat plans — mirror the app's
 * dashboard/progress-reports/{index,show}.blade.php and
 * dashboard/seat-plans/{index,show}.blade.php.
 */

const GRADING_SCALE = [
  { min: 80, max: 100, grade: "A+", points: 4.0, remark: "Excellent" },
  { min: 70, max: 79, grade: "A", points: 3.7, remark: "Very Good" },
  { min: 65, max: 69, grade: "A-", points: 3.3, remark: "Good" },
  { min: 60, max: 64, grade: "B+", points: 3.0, remark: "Above Average" },
  { min: 55, max: 59, grade: "B", points: 2.7, remark: "Average" },
  { min: 50, max: 54, grade: "B-", points: 2.3, remark: "Satisfactory" },
  { min: 45, max: 49, grade: "C+", points: 2.0, remark: "Below Average" },
  { min: 40, max: 44, grade: "C", points: 1.7, remark: "Pass" },
  { min: 0, max: 39, grade: "F", points: 0.0, remark: "Fail" },
];

function gradeFor(score: number): { grade: string; points: number; remark: string } {
  for (const row of GRADING_SCALE) {
    if (score >= row.min && score <= row.max) return { grade: row.grade, points: row.points, remark: row.remark };
  }
  return { grade: "F", points: 0, remark: "Fail" };
}

const selectClass =
  "rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500";

type Row = Record<string, unknown>;

/** dashboard/progress-reports — pick a student and generate/preview their report. */
export async function ProgressReportsIndex({
  classId,
  sectionId,
  batchId,
  page,
}: {
  classId?: number;
  sectionId?: number;
  batchId?: number;
  page?: number;
}) {
  const [classes, sections, batches] = await Promise.all([
    prisma.school_classes.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }),
    prisma.sections.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }),
    prisma.batches.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }),
  ]);

  const perPage = 20;
  const currentPage = Math.max(1, page ?? 1);
  const where = {
    deleted_at: null,
    ...(classId ? { class_id: classId } : {}),
    ...(sectionId ? { section_id: sectionId } : {}),
    ...(batchId ? { batch_id: batchId } : {}),
  };
  const [total, students] = await Promise.all([
    prisma.students.count({ where }),
    prisma.students.findMany({
      where,
      orderBy: { id: "asc" },
      skip: (currentPage - 1) * perPage,
      take: perPage,
      include: { users: true, school_classes: true, sections: true },
    }),
  ]);
  const lastPage = Math.max(1, Math.ceil(total / perPage));

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Progress Reports</h1>
      </div>

      <form method="get" className="mb-6 flex flex-wrap gap-3">
        <select name="class_id" className={selectClass} defaultValue={classId ?? ""}>
          <option value="">All classes</option>
          {classes.map((c) => (
            <option key={c.id} value={c.id}>{String(c.name)}</option>
          ))}
        </select>
        <select name="section_id" className={selectClass} defaultValue={sectionId ?? ""}>
          <option value="">All sections</option>
          {sections.map((s) => (
            <option key={s.id} value={s.id}>{String(s.name)}</option>
          ))}
        </select>
        <select name="batch_id" className={selectClass} defaultValue={batchId ?? ""}>
          <option value="">All batches</option>
          {batches.map((b) => (
            <option key={b.id} value={b.id}>{String(b.name ?? `#${b.id}`)}</option>
          ))}
        </select>
        <button type="submit" className="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
      </form>

      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">#</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Student</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Class</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Section</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Roll</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {students.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-400">
                  No students found. Select a class with students to generate progress reports.
                </td>
              </tr>
            ) : (
              students.map((s) => {
                const student = s as unknown as Row;
                const user = (student.users ?? {}) as Row;
                const schoolClass = (student.school_classes ?? {}) as Row;
                const section = (student.sections ?? {}) as Row;
                return (
                  <tr key={String(student.id)} className="hover:bg-gray-50">
                    <td className="px-4 py-3">{String(student.id)}</td>
                    <td className="px-4 py-3">{String(user.name ?? "N/A")}</td>
                    <td className="px-4 py-3">{String(schoolClass.name ?? "N/A")}</td>
                    <td className="px-4 py-3">{String(section.name ?? "N/A")}</td>
                    <td className="px-4 py-3">{String(student.roll_number ?? "N/A")}</td>
                    <td className="px-4 py-3">
                      <a href={`/dashboard/progress-reports/${String(student.id)}/generate`} className="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">
                        Generate Progress Report
                      </a>
                      <a
                        href={`/dashboard/progress-reports/${String(student.id)}/generate?view=1`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="ml-2 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                      >
                        Preview
                      </a>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {lastPage > 1 ? (
        <div className="mt-4 flex items-center gap-2 text-sm">
          <Link href={`/dashboard/progress-reports?page=${Math.max(1, currentPage - 1)}${classId ? `&class_id=${classId}` : ""}${sectionId ? `&section_id=${sectionId}` : ""}${batchId ? `&batch_id=${batchId}` : ""}`} className="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50">
            ← Prev
          </Link>
          <span className="text-slate-600">Page {currentPage} of {lastPage}</span>
          <Link href={`/dashboard/progress-reports?page=${Math.min(lastPage, currentPage + 1)}${classId ? `&class_id=${classId}` : ""}${sectionId ? `&section_id=${sectionId}` : ""}${batchId ? `&batch_id=${batchId}` : ""}`} className="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50">
            Next →
          </Link>
        </div>
      ) : null}
    </div>
  );
}

/** dashboard/progress-reports/{student}/generate — printable report (mirrors show.blade.php). */
export async function ProgressReportShow({ studentId, view }: { studentId: number; view?: boolean }) {
  const student = await prisma.students.findUnique({
    where: { id: studentId },
    include: { users: true, school_classes: true, sections: true, batches: true },
  });
  if (!student) return <p className="p-16 text-center text-slate-500">Student not found.</p>;

  const settings = await getSiteSettings();
  const results = await prisma.exam_results.findMany({
    where: { student_id: studentId },
    include: { exams: { include: { subjects: true } } },
  });

  const rows: Array<{ exam_name: string; subject: string; obtained: number; total: number; percentage: number; grade: string; remark: string }> = [];
  let totalObtained = 0;
  let totalPossible = 0;

  for (const result of results as unknown as Row[]) {
    const exam = (result.exams ?? {}) as Row;
    if (!exam.id) continue;
    const obtained = Number(result.obtained_marks ?? 0);
    const total = Number(exam.total_marks ?? 0);
    const percentage = total > 0 ? Math.round((obtained / total) * 10000) / 100 : 0;
    const grade = gradeFor(percentage);
    totalObtained += obtained;
    totalPossible += total;
    rows.push({
      exam_name: String(exam.name ?? ""),
      subject: String((exam.subjects as Row | undefined)?.name ?? "N/A"),
      obtained,
      total,
      percentage,
      grade: grade.grade,
      remark: grade.remark,
    });
  }

  const overallPercentage = totalPossible > 0 ? Math.round((totalObtained / totalPossible) * 10000) / 100 : 0;
  const overall = gradeFor(overallPercentage);

  const submissions = await prisma.assignment_submissions.findMany({
    where: { student_id: studentId, marks: { not: null } },
    include: { assignments: { include: { subjects: true } } },
  });
  const assignmentRows: Array<{ title: string; subject: string; marks: number; total: number; percentage: number }> = [];
  let assignmentTotalPercentage = 0;
  let assignmentCount = 0;
  for (const sub of submissions as unknown as Row[]) {
    const assignment = (sub.assignments ?? {}) as Row;
    const marks = Number(sub.marks ?? 0);
    const total = Number(assignment.total_marks ?? 0);
    const pct = total > 0 ? Math.round((marks / total) * 10000) / 100 : 0;
    assignmentTotalPercentage += pct;
    assignmentCount++;
    assignmentRows.push({
      title: String(assignment.title ?? "Assignment"),
      subject: String((assignment.subjects as Row | undefined)?.name ?? "N/A"),
      marks,
      total,
      percentage: pct,
    });
  }
  const assignmentAverage = assignmentCount > 0 ? Math.round((assignmentTotalPercentage / assignmentCount) * 100) / 100 : null;

  const user = (student.users ?? {}) as Row;
  const schoolClass = (student.school_classes ?? {}) as Row;
  const section = (student.sections ?? {}) as Row;
  const batch = (student.batches ?? {}) as Row;
  const generatedAt = new Date();

  return (
    <div>
      {view ? (
        <div className="mb-4 flex gap-2">
          <a href={`/dashboard/progress-reports/${studentId}/generate`} className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Download PDF</a>
        </div>
      ) : null}
      <div style={{ fontFamily: "'Helvetica Neue', Arial, sans-serif", color: "#1f2937", background: "#f5f5f5", padding: "2rem" }}>
        <div className="report" style={{ maxWidth: 800, margin: "0 auto", background: "#fff", padding: "2.5rem", border: "1px solid #e5e7eb" }}>
          <div className="header" style={{ display: "flex", alignItems: "center", gap: "1rem", borderBottom: "2px solid #2563eb", paddingBottom: "1rem", marginBottom: "1.5rem" }}>
            <div>
              <div style={{ fontSize: "1.5rem", fontWeight: "bold", color: "#1e40af" }}>{settings.schoolName}</div>
              <div style={{ fontSize: "0.8rem", color: "#6b7280" }}>{settings.address}</div>
            </div>
          </div>

          <div style={{ textAlign: "center", fontSize: "1.25rem", fontWeight: "bold", textTransform: "uppercase", letterSpacing: "1px", marginBottom: "1.5rem", color: "#111827" }}>
            Student Progress Report
          </div>

          <div style={{ display: "grid", gridTemplateColumns: "repeat(2, minmax(0, 1fr))", gap: "0.5rem 2rem", fontSize: "0.9rem", marginBottom: "1.5rem" }}>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Name:</span> {String(user.name ?? "N/A")}</div>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Admission No:</span> {String(student.admission_number ?? student.admission_no ?? "N/A")}</div>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Class:</span> {String(schoolClass.name ?? "N/A")}</div>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Section:</span> {String(section.name ?? "N/A")}</div>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Batch:</span> {String(batch.name ?? "N/A")}</div>
            <div><span style={{ fontWeight: 600, color: "#374151" }}>Roll Number:</span> {String(student.roll_number ?? "N/A")}</div>
          </div>

          <div style={{ fontSize: "1rem", fontWeight: "bold", color: "#1e40af", margin: "1.5rem 0 0.75rem", borderLeft: "4px solid #2563eb", paddingLeft: "0.5rem" }}>
            Examination Results
          </div>
          {rows.length > 0 ? (
            <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "0.85rem", marginBottom: "0.5rem" }}>
              <thead>
                <tr>
                  {["Exam", "Subject", "Obtained", "Total", "Percentage", "Grade", "Remark"].map((h) => (
                    <th key={h} style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem", textAlign: "left", background: "#eff6ff", fontWeight: 600, color: "#1e3a8a" }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {rows.map((row, i) => (
                  <tr key={i}>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.exam_name}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.subject}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.obtained}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.total}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.percentage}%</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.grade}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.remark}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p style={{ fontSize: "0.9rem", color: "#6b7280" }}>No exam results available.</p>
          )}

          <div style={{ fontSize: "1rem", fontWeight: "bold", color: "#1e40af", margin: "1.5rem 0 0.75rem", borderLeft: "4px solid #2563eb", paddingLeft: "0.5rem" }}>
            Assignment Submissions
          </div>
          {assignmentAverage !== null ? (
            <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "0.85rem", marginBottom: "0.5rem" }}>
              <thead>
                <tr>
                  {["Assignment", "Subject", "Marks", "Total", "Percentage"].map((h) => (
                    <th key={h} style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem", textAlign: "left", background: "#eff6ff", fontWeight: 600, color: "#1e3a8a" }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {assignmentRows.map((row, i) => (
                  <tr key={i}>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.title}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.subject}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.marks}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.total}</td>
                    <td style={{ border: "1px solid #d1d5db", padding: "0.5rem 0.6rem" }}>{row.percentage}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p style={{ fontSize: "0.9rem", color: "#6b7280" }}>No assignment submissions available.</p>
          )}

          <div style={{ display: "flex", flexWrap: "wrap", gap: "1.5rem", marginTop: "1rem", fontSize: "0.95rem" }}>
            <div style={{ background: "#f8fafc", border: "1px solid #e2e8f0", borderRadius: "0.5rem", padding: "0.75rem 1rem" }}>
              Overall Percentage
              <strong style={{ display: "block", fontSize: "1.1rem", color: "#1e40af" }}>{overallPercentage}%</strong>
            </div>
            <div style={{ background: "#f8fafc", border: "1px solid #e2e8f0", borderRadius: "0.5rem", padding: "0.75rem 1rem" }}>
              Overall Grade
              <strong style={{ display: "block", fontSize: "1.1rem", color: "#1e40af" }}>{overall.grade} ({overall.points})</strong>
            </div>
            <div style={{ background: "#f8fafc", border: "1px solid #e2e8f0", borderRadius: "0.5rem", padding: "0.75rem 1rem" }}>
              Assignment Average
              <strong style={{ display: "block", fontSize: "1.1rem", color: "#1e40af" }}>{assignmentAverage !== null ? `${assignmentAverage}%` : "N/A"}</strong>
            </div>
          </div>

          <div style={{ marginTop: "2rem", display: "flex", justifyContent: "space-between", alignItems: "flex-end", fontSize: "0.8rem", color: "#6b7280" }}>
            <div>Generated: {generatedAt.toLocaleString("en-US", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" })}</div>
            <div style={{ borderTop: "1px solid #334155", width: 200, paddingTop: "0.5rem", textAlign: "center" }}>Authorized Signature</div>
          </div>
        </div>
      </div>
    </div>
  );
}

/** dashboard/seat-plans — list exams and generate/preview seat plans. */
export async function SeatPlansIndex({ published, page }: { published?: string; page?: number }) {
  const perPage = 20;
  const currentPage = Math.max(1, page ?? 1);
  const where =
    published === "1" || published === "0"
      ? { is_published: published === "1" }
      : {};
  const [total, exams] = await Promise.all([
    prisma.exams.count({ where }),
    prisma.exams.findMany({
      where,
      orderBy: { id: "desc" },
      skip: (currentPage - 1) * perPage,
      take: perPage,
      include: { batches: true, sections: true },
    }),
  ]);
  const lastPage = Math.max(1, Math.ceil(total / perPage));

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Seat plans</h1>
      </div>

      <form method="get" className="mb-6 flex flex-wrap gap-3">
        <select name="published" className={selectClass} defaultValue={published ?? ""}>
          <option value="">All exams</option>
          <option value="1">Published only</option>
          <option value="0">Unpublished only</option>
        </select>
        <button type="submit" className="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
      </form>

      <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">#</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Exam</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Batch</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Section</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {exams.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-16 text-center text-sm text-slate-400">
                  No exams found. Seat plans will appear here once exams are created.
                </td>
              </tr>
            ) : (
              exams.map((e) => {
                const exam = e as unknown as Row;
                const batch = (exam.batches ?? {}) as Row;
                const section = (exam.sections ?? {}) as Row;
                return (
                  <tr key={String(exam.id)} className="hover:bg-gray-50">
                    <td className="px-4 py-3">{String(exam.id)}</td>
                    <td className="px-4 py-3">{String(exam.name ?? "")}</td>
                    <td className="px-4 py-3">{String(batch.name ?? "N/A")}</td>
                    <td className="px-4 py-3">{String(section.name ?? "N/A")}</td>
                    <td className="px-4 py-3">
                      {exam.start_date ? new Date(String(exam.start_date)).toLocaleDateString("en-US", { day: "2-digit", month: "short", year: "numeric" }) : "N/A"}
                    </td>
                    <td className="px-4 py-3">
                      <a href={`/dashboard/seat-plans/${String(exam.id)}/generate?view=1`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800">
                        Preview
                      </a>
                      <a href={`/dashboard/seat-plans/${String(exam.id)}/generate`} className="ml-2 text-brand-600 hover:text-brand-800">
                        Generate Seat Plan
                      </a>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {lastPage > 1 ? (
        <div className="mt-4 flex items-center gap-2 text-sm">
          <Link href={`/dashboard/seat-plans?page=${Math.max(1, currentPage - 1)}${published ? `&published=${published}` : ""}`} className="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50">
            ← Prev
          </Link>
          <span className="text-slate-600">Page {currentPage} of {lastPage}</span>
          <Link href={`/dashboard/seat-plans?page=${Math.min(lastPage, currentPage + 1)}${published ? `&published=${published}` : ""}`} className="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50">
            Next →
          </Link>
        </div>
      ) : null}
    </div>
  );
}

/** dashboard/seat-plans/{exam}/generate — printable seat plan (mirrors show.blade.php). */
export async function SeatPlanShow({ examId, perRoom, view }: { examId: number; perRoom?: number; view?: boolean }) {
  const perRoomSafe = Number(perRoom && perRoom > 0 ? perRoom : 30);
  const exam = await prisma.exams.findUnique({
    where: { id: examId },
    include: { batches: true, sections: true },
  });
  if (!exam) return <p className="p-16 text-center text-slate-500">Exam not found.</p>;

  const settings = await getSiteSettings();
  const students = await prisma.students.findMany({
    where: { batch_id: exam.batch_id ?? undefined, ...(exam.section_id ? { section_id: exam.section_id } : {}) },
    orderBy: { roll_number: "asc" },
    include: { users: true },
  });

  const rooms: Record<string, typeof students> = {};
  let roomNumber = 1;
  for (let i = 0; i < students.length; i += perRoomSafe) {
    rooms[`Room-${roomNumber}`] = students.slice(i, i + perRoomSafe);
    roomNumber++;
  }

  const examRow = exam as unknown as Row;
  const batch = (exam.batches ?? {}) as Row;
  const section = (exam.sections ?? {}) as Row;
  const date = exam.start_date ? new Date(exam.start_date).toLocaleDateString("en-US", { day: "2-digit", month: "short", year: "numeric" }) : "N/A";

  return (
    <div>
      {view ? (
        <div className="mb-4 flex gap-2">
          <form method="get" className="flex items-center gap-2">
            <label className="text-sm text-slate-600">
              Students per room:
              <input type="number" name="per_room" defaultValue={perRoomSafe} min={1} className="ml-1 w-20 rounded border border-slate-300 px-2 py-1 text-sm" />
            </label>
            <button type="submit" className="rounded bg-slate-100 px-3 py-1 text-sm hover:bg-slate-200">Apply</button>
          </form>
          <a href={`/dashboard/seat-plans/${examId}/generate`} className="ml-2 text-sm text-brand-600 hover:text-brand-800">Download PDF</a>
        </div>
      ) : null}

      <div style={{ fontFamily: "Arial, sans-serif", margin: 0, padding: "24px", color: "#111" }}>
        <div style={{ textAlign: "center", borderBottom: "3px solid #1e40af", paddingBottom: "12px", marginBottom: "18px" }}>
          <h1 style={{ margin: 0, fontSize: "24px", color: "#1e40af" }}>{settings.schoolName}</h1>
          {settings.tagline ? <div style={{ fontSize: "13px", color: "#666" }}>{settings.tagline}</div> : null}
          {settings.address ? <div style={{ fontSize: "12px", color: "#888" }}>{settings.address}</div> : null}
        </div>

        <div style={{ textAlign: "center", marginBottom: "22px", fontSize: "15px" }}>
          <div><strong>Exam:</strong> {String(examRow.name ?? "")}</div>
          <div>
            <strong>Date:</strong> {date} &nbsp; | &nbsp; <strong>Batch:</strong> {String(batch.name ?? "N/A")} &nbsp; | &nbsp; <strong>Section:</strong> {String(section.name ?? "N/A")}
          </div>
          <div><strong>Students per room:</strong> {perRoomSafe}</div>
        </div>

        {Object.keys(rooms).length === 0 ? (
          <p style={{ textAlign: "center" }}>No students found for this exam.</p>
        ) : (
          Object.entries(rooms).map(([roomName, roomStudents]) => (
            <div key={roomName} style={{ pageBreakInside: "avoid", border: "2px solid #000", borderRadius: "8px", padding: "14px", marginBottom: "20px" }}>
              <div style={{ fontSize: "17px", fontWeight: "bold", textAlign: "center", borderBottom: "1px dashed #999", paddingBottom: "8px", marginBottom: "12px" }}>{roomName}</div>
              <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "13px" }}>
                <thead>
                  <tr>
                    <th style={{ width: "12%", border: "1px solid #444", padding: "6px 8px", textAlign: "left", background: "#f1f5f9" }}>Seat</th>
                    <th style={{ width: "18%", border: "1px solid #444", padding: "6px 8px", textAlign: "left", background: "#f1f5f9" }}>Roll</th>
                    <th style={{ border: "1px solid #444", padding: "6px 8px", textAlign: "left", background: "#f1f5f9" }}>Student Name</th>
                  </tr>
                </thead>
                <tbody>
                  {roomStudents.map((s, index) => {
                    const student = s as unknown as Row;
                    const user = (student.users ?? {}) as Row;
                    const name =
                      String(user.name ?? "") || `${String(student.first_name ?? "")} ${String(student.last_name ?? "")}`.trim();
                    return (
                      <tr key={String(student.id)}>
                        <td style={{ border: "1px solid #444", padding: "6px 8px" }}>{index + 1}</td>
                        <td style={{ border: "1px solid #444", padding: "6px 8px" }}>{String(student.roll_number ?? "N/A")}</td>
                        <td style={{ border: "1px solid #444", padding: "6px 8px" }}>{name}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ))
        )}

        <div style={{ marginTop: "30px", display: "flex", justifyContent: "space-between", fontSize: "13px" }}>
          <div>Prepared by: ___________________</div>
          <div>Invigilator: ___________________</div>
        </div>
      </div>
    </div>
  );
}