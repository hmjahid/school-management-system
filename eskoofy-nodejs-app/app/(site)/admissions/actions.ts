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

/** Mirrors the app's `admissions.scholarship.store` (SitePageController@scholarshipStore). */
export async function submitScholarship(formData: FormData): Promise<void> {
  const value = (key: string) => String(formData.get(key) ?? "").trim();
  const name = value("name");
  const email = value("email");
  const message = value("message");

  if (!name || !email || !message) {
    redirect("/admissions?error=1");
  }

  try {
    await prisma.contact_submissions.create({
      data: {
        type: "scholarship",
        name,
        email,
        phone: value("phone") || null,
        subject: "Scholarship application",
        message,
      },
    });
  } catch {
    redirect("/admissions?error=1");
  }

  redirect("/admissions?sent=1");
}

/** Mirrors the app's `admissions.submit-payment` (AdmissionWebController@submitTransaction). */
export async function submitPayment(formData: FormData): Promise<void> {
  const id = Number(formData.get("id"));
  const transactionId = String(formData.get("transaction_id") ?? "").trim();
  const paymentMethod = String(formData.get("payment_method") ?? "").trim();

  if (!id || !transactionId || !paymentMethod) {
    redirect("/admissions/status?error=1");
  }

  let existing: { id: number; application_number: string; payment_status: string } | null = null;
  try {
    existing = await prisma.admissions.findUnique({
      where: { id },
      select: { id: true, application_number: true, payment_status: true },
    });
    if (!existing || existing.payment_status !== "unpaid") {
      redirect(`/admissions/status?application_number=${encodeURIComponent(existing?.application_number ?? "")}&error=1`);
    }
    await prisma.admissions.update({
      where: { id },
      data: {
        transaction_id: transactionId,
        payment_method: paymentMethod,
        payment_status: "submitted",
        paid_at: new Date(),
      },
    });
  } catch {
    redirect("/admissions/status?error=1");
  }

  redirect(`/admissions/status?application_number=${encodeURIComponent(existing.application_number)}&sent=1`);
}
