/**
 * Demo attendance mirror — eskoofy-laravel-app/database/seeders/DemoAttendanceSeeder.php.
 *
 * The real seeder marks each demo student present for a window of dates. This
 * models the same generator (per student per date, status present/absent)
 * without inventing rows beyond the pattern the seeder encodes.
 */

export const DEMO_ATTENDANCE_WINDOW_DAYS = 30;

export type DemoAttendanceStatus = "present" | "absent" | "late";

export function demoAttendanceRows(
  studentIds: number[],
  startDate: string,
): Array<{ student_id: number; date: string; status: DemoAttendanceStatus }> {
  const rows: Array<{ student_id: number; date: string; status: DemoAttendanceStatus }> = [];
  for (const sid of studentIds) {
    for (let day = 0; day < DEMO_ATTENDANCE_WINDOW_DAYS; day++) {
      rows.push({
        student_id: sid,
        date: startDate,
        status: day % 6 === 0 ? "absent" : "present",
      });
    }
  }
  return rows;
}
