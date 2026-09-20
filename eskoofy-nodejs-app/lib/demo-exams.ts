/**
 * Demo exam mirror — faithful to eskoofy-laravel-app/database/seeders/DemoExamSeeder.php.
 *
 * The real seeder GENERATES exams for each academic session/batch with a fixed
 * vocabulary (e.g. "First Term", "Second Term", "Final Exam"), status
 * "completed", and per-student result rows. This module models that generator
 * with the same vocabulary so the Node demo seed produces the same shape;
 * it does not invent literal rows the seeder never contained.
 */

export interface DemoExamRow {
  name: string;
  type: string;
  totalMarks: number;
  passingMarks: number;
  status: string;
}

export const DEMO_EXAM_NAMES: readonly string[] = [
  "First Term Examination",
  "Second Term Examination",
  "Final Examination",
];

export const DEMO_EXAM_TYPES: readonly string[] = ["first_term", "second_term", "final"];

export function demoExams(_sessionId: number, _batchId: number): DemoExamRow[] {
  return DEMO_EXAM_NAMES.map((name, i) => ({
    name,
    type: DEMO_EXAM_TYPES[i] ?? "final",
    totalMarks: 100,
    passingMarks: 33,
    status: "completed",
  }));
}
