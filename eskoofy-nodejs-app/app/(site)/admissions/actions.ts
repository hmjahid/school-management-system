"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

function generateApplicationNumber(): string {
  const stamp = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  const random = Math.floor(Math.random() * 9000 + 1000);
  return `APP-${stamp}-${random}`;
}

/** Mirrors the app's `admissions.apply` (store) handler. */
export async function submitAdmission(formData: FormData): Promise<void> {
  const value = (key: string) => String(formData.get(key) ?? "").trim();

  const applicationNumber = generateApplicationNumber();
  const firstName = value("first_name");
  const lastName = value("last_name");
  const email = value("email");
  const sessionId = Number(value("academic_session_id"));
  const batchId = Number(value("batch_id"));

  if (!firstName || !lastName || !email || !sessionId || !batchId) {
    redirect("/admissions/apply?error=1");
  }

  try {
    await prisma.admissions.create({
      data: {
        application_number: applicationNumber,
        academic_session_id: sessionId,
        batch_id: batchId,
        first_name: firstName,
        last_name: lastName,
        gender: value("gender") || "other",
        date_of_birth: value("date_of_birth") ? new Date(value("date_of_birth")) : new Date(),
        email,
        phone: value("phone"),
        address: value("address"),
        city: value("city"),
        postal_code: value("postal_code"),
        country: value("country") || "Bangladesh",
        father_name: value("father_name"),
        father_phone: value("father_phone"),
        mother_name: value("mother_name"),
        mother_phone: value("mother_phone"),
        status: "pending",
        submitted_at: new Date(),
      },
    });
  } catch {
    redirect("/admissions/apply?error=1");
  }

  redirect(`/admissions/status?application_number=${encodeURIComponent(applicationNumber)}`);
}
