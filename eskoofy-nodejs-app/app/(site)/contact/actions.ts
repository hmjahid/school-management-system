"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

/** Mirrors the app's `site.contact` / complaint / feedback form handlers. */
export async function submitContact(formData: FormData): Promise<void> {
  const type = String(formData.get("type") ?? "contact");
  const name = String(formData.get("name") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim();
  const phone = String(formData.get("phone") ?? "").trim();
  const subject = String(formData.get("subject") ?? "").trim();
  const message = String(formData.get("message") ?? "").trim();

  if (!name || !email || !message) redirect("/contact?error=1");

  try {
    await prisma.contact_submissions.create({
      data: {
        type,
        name,
        email,
        phone: phone || null,
        subject: subject || null,
        message,
      },
    });
  } catch {
    redirect("/contact?error=1");
  }

  redirect("/contact?sent=1");
}
