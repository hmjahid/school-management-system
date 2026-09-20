/**
 * Demo leave types mirror — exact rows from eskoofy-laravel-app/database/seeders/DemoLeaveSeeder.php.
 */
export interface DemoLeaveRow {
  name: string;
  daysPerYear: number;
  isPaid: boolean;
  isActive: boolean;
}

export const demoLeaveTypes: readonly DemoLeaveRow[] = [
  { name: "Sick Leave", daysPerYear: 14, isPaid: true, isActive: true },
  { name: "Casual Leave", daysPerYear: 10, isPaid: true, isActive: true },
  { name: "Annual Leave", daysPerYear: 30, isPaid: true, isActive: true },
  { name: "Maternity Leave", daysPerYear: 120, isPaid: true, isActive: true },
  { name: "Paternity Leave", daysPerYear: 7, isPaid: true, isActive: true },
];
