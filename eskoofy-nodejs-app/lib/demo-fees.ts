/**
 * A2 — demo fees mirror. Same field shape Laravel's DatabaseSeeder writes for
 * fees (see eskoofy-laravel-app/database/seeders/DemoFeeSeeder.php), values
 * reproduced exactly, no invented rows.
 */

export interface DemoFeeRow {
  name: string;
  code: string;
  amount: number;
  feeType: "monthly" | "quarterly" | "yearly";
  classId: number;
  status: boolean;
}

export const demoFees: readonly DemoFeeRow[] = [
  { name: "Monthly Tuition Fee", code: "FEE-MONTHLY-TUITION", amount: 1200, feeType: "monthly", classId: 1, status: true },
  { name: "Annual Admission Fee", code: "FEE-ANNUAL-ADMISSION", amount: 3000, feeType: "yearly", classId: 1, status: true },
  { name: "Lab Fee", code: "FEE-LAB", amount: 500, feeType: "monthly", classId: 3, status: true },
  { name: "Sports Fee", code: "FEE-SPORTS", amount: 800, feeType: "quarterly", classId: 5, status: false },
];
