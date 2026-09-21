"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";

/** Mirrors PortalController@messageTeacher (POST /portal/message). */
export async function sendTeacherMessage(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/portal");

  const teacherId = Number(formData.get("teacher_id"));
  const subject = String(formData.get("subject") ?? "").trim();
  const body = String(formData.get("body") ?? "").trim();

  if (!teacherId || !subject || !body) {
    redirect("/portal?error=1");
  }

  try {
    const teacher = await prisma.teachers.findUnique({ where: { id: teacherId } });
    if (!teacher) redirect("/portal?error=1");
    await prisma.messages.create({
      data: {
        sender_id: user.id,
        receiver_id: teacher.user_id,
        subject,
        body,
      },
    });
  } catch {
    redirect("/portal?error=1");
  }

  redirect("/portal?sent=1");
}