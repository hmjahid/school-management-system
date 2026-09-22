"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";

/**
 * Server action for the staff attendance sheet — mirrors
 * `DashboardStaffAttendanceController@store`: upsert one row per teacher,
 * keyed on (teacher_id, date).
 */
export async function saveStaffAttendance(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login");
  if (!can(user.role, "manage_attendance")) redirect("/dashboard/staff-attendance?error=1");

  const dateRaw = String(formData.get("date") ?? "").trim();
  const statuses = formData.getAll("status").map(String);
  const notes = formData.getAll("note").map(String);
  const ids = formData.getAll("teacher_id").map((id) => Number(id));

  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateRaw)) redirect("/dashboard/staff-attendance?error=1");

  const date = new Date(`${dateRaw}T00:00:00`);

  for (let i = 0; i < ids.length; i++) {
    const teacherId = ids[i];
    if (!teacherId) continue;
    const status = statuses[i] ?? "present";
    const noteContent = (notes[i] ?? "").trim() || null;

    await prisma.staff_attendances.upsert({
      where: { teacher_id_date: { teacher_id: teacherId, date } },
      create: { teacher_id: teacherId, date, status, note: noteContent, recorded_by: user.id },
      update: { status, note: noteContent, recorded_by: user.id },
    });
  }

  revalidatePath("/dashboard/staff-attendance");
  redirect(`/dashboard/staff-attendance?date=${dateRaw}&saved=1`);
}