/**
 * Demo users mirror — eskoofy-laravel-app/database/seeders/DemoUsersSeeder.php.
 */
export interface DemoUserRow {
  name: string;
  email: string;
  phone: string;
  gender: string;
  roleId: number;
}

export const demoUsers: readonly DemoUserRow[] = [
  { name: "Admin User", email: "admin@eskoofy.edu", phone: "01800000001", gender: "male", roleId: 1 },
  { name: "Teacher One", email: "teacher1@eskoofy.edu", phone: "01800000002", gender: "male", roleId: 2 },
  { name: "Staff User", email: "staff@eskoofy.edu", phone: "01800000003", gender: "female", roleId: 3 },
];
