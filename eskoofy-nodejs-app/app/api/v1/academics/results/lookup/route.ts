import { NextRequest } from "next/server";
import { prisma } from "@/lib/prisma";
import { error, notFound, success, validationError } from "@/lib/api-response";

export const dynamic = "force-dynamic";

/**
 * `GET /api/v1/academics/results/lookup?roll=R-001&exam=12`
 *
 * Mirrors the app's `ResultController@lookup` (the same lookup behind the
 * public `/results` page). Returns the `{success,message,data}` envelope.
 */
export async function GET(request: NextRequest) {
  const roll = (request.nextUrl.searchParams.get("roll") ?? "").trim();
  const examId = Number(request.nextUrl.searchParams.get("exam") ?? 0);

  if (roll === "") {
    return validationError({ roll: ["The roll field is required."] });
  }

  try {
    const student = await prisma.students.findFirst({
      where: { OR: [{ roll_number: roll }, { roll_no: roll }], deleted_at: null },
    });
    if (!student) return notFound("No student found for that roll number.");

    const results = await prisma.exam_results.findMany({
      where: { student_id: student.id, ...(examId ? { exam_id: examId } : {}) },
      include: { exams: true },
      take: 50,
    });

    return success({
      student: {
        id: student.id,
        roll_number: student.roll_number ?? student.roll_no,
        name: `${student.first_name} ${student.last_name}`.trim(),
      },
      results: results.map((result) => ({
        exam_id: result.exam_id,
        exam_name: result.exams?.name ?? null,
        total_marks: result.exams?.total_marks ?? null,
        obtained_marks: result.obtained_marks,
        grade: result.grade,
        remarks: result.remarks,
      })),
    });
  } catch {
    return error("Unable to look up results right now.", 503);
  }
}
