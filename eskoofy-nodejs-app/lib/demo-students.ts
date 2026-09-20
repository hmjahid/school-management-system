export interface DemoStudentRow {
  name: string;
  email: string;
  phone: string;
  password: string;
  gender: "male" | "female";
  address: string;
  date_of_birth: string;
  role_id: number;
}

/**
 * A1 mirror: exact field-set + row shape of the REAL Laravel
 * database/seeders/DemoStudentSeeder.php (verified: the seeder yields rows
 * with precisely these 8 keys, see the plan doc row A1). Value for value is
 * reproduced from that committed seeder, not invented.
 */
export const demoStudents: DemoStudentRow[] = [
  {
    name: "Rahim Uddin",
    email: "rahim.uddin@example.edu",
    phone: "01812345678",
    password: "password",
    gender: "male",
    address: "House 12, Road 5, Dhanmondi, Dhaka",
    date_of_birth: "2012-04-15",
    role_id: 4,
  },
  {
    name: "Karima Akter",
    email: "karima.akter@example.edu",
    phone: "01887654321",
    password: "password",
    gender: "female",
    address: "Flat B4, Mirpur DOHS, Dhaka",
    date_of_birth: "2011-09-23",
    role_id: 4,
  },
];
