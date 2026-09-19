/**
 * Seed the minimum demo data so the dashboard renders after `db:seed`.
 *
 * Uses the same canonical demo accounts as the Laravel app
 * (`docs/operations/DEMO-CREDENTIALS.md`) so the clone behaves identically:
 *   admin@school.com     / ChangeMe!2026$Tr0ng   (admin)
 *   teacher1@school.com  / password              (teacher)
 *   student1@school.com  / password              (student)
 *
 * Usage: npm run db:seed
 */
import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? "admin@school.com";
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? "ChangeMe!2026$Tr0ng";
const DEMO_PASSWORD = "password";

async function ensureRole(name: string) {
  const existing = await prisma.roles.findFirst({ where: { name } });
  if (existing) return existing;
  return prisma.roles.create({ data: { name, guard_name: "web" } });
}

async function main(): Promise<void> {
  const adminPassword = await bcrypt.hash(ADMIN_PASSWORD, 12);
  const demoPassword = await bcrypt.hash(DEMO_PASSWORD, 12);

  const adminRole = await ensureRole("admin");
  const studentRole = await ensureRole("student");
  const teacherRole = await ensureRole("teacher");

  const admin = await prisma.users.upsert({
    where: { email: ADMIN_EMAIL },
    update: {},
    create: {
      name: "Super Administrator",
      email: ADMIN_EMAIL,
      password: adminPassword,
      role_id: adminRole.id,
      role: "admin",
    },
  });

  const schoolClass = await prisma.school_classes.create({
    data: {
      name: "Class 1",
      max_students: 40,
      is_active: true,
      monthly_fee: 0,
      admission_fee: 0,
      exam_fee: 0,
      other_fees: 0,
    },
  });

  const studentUser = await prisma.users.upsert({
    where: { email: "student1@school.com" },
    update: {},
    create: {
      name: "Demo Student",
      email: "student1@school.com",
      password: demoPassword,
      role_id: studentRole.id,
      role: "student",
    },
  });

  await prisma.students.create({
    data: {
      user_id: studentUser.id,
      class_id: schoolClass.id,
      admission_number: "ADM-0001",
      admission_date: new Date(),
      first_name: "Demo",
      last_name: "Student",
      nationality: "Bangladeshi",
      country: "Bangladesh",
      monthly_fee: 0,
      transport_fee: 0,
      discount: 0,
      status: "active",
    },
  });

  const teacherUser = await prisma.users.upsert({
    where: { email: "teacher1@school.com" },
    update: {},
    create: {
      name: "Demo Teacher",
      email: "teacher1@school.com",
      password: demoPassword,
      role_id: teacherRole.id,
      role: "teacher",
    },
  });

  await prisma.teachers.create({
    data: {
      user_id: teacherUser.id,
      nationality: "Bangladeshi",
      salary: 0,
      salary_type: "monthly",
      status: "active",
    },
  });

  console.log(`Seeded admin user #${admin.id} (${ADMIN_EMAIL} / ${ADMIN_PASSWORD})`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
