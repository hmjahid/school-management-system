/**
 * Seed the minimum demo data so the dashboard renders after `db:seed`.
 * Mirrors the demo credentials in `docs/operations/DEMO-CREDENTIALS.md`.
 *
 * Usage: npm run db:seed
 */
import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function ensureRole(name: string) {
  const existing = await prisma.roles.findFirst({ where: { name } });
  if (existing) return existing;
  return prisma.roles.create({ data: { name, guard_name: "web" } });
}

async function main(): Promise<void> {
  const password = await bcrypt.hash("admin123", 12);

  const adminRole = await ensureRole("admin");
  const studentRole = await ensureRole("student");
  const teacherRole = await ensureRole("teacher");

  const admin = await prisma.users.upsert({
    where: { email: "admin@eskoofy.com" },
    update: {},
    create: {
      name: "Eskoofy Admin",
      email: "admin@eskoofy.com",
      password,
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
    where: { email: "student@eskoofy.com" },
    update: {},
    create: {
      name: "Demo Student",
      email: "student@eskoofy.com",
      password,
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
    where: { email: "teacher@eskoofy.com" },
    update: {},
    create: {
      name: "Demo Teacher",
      email: "teacher@eskoofy.com",
      password,
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

  console.log(`Seeded admin user #${admin.id} (admin@eskoofy.com / admin123)`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
