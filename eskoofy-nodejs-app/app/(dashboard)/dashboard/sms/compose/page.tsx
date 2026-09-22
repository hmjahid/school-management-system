import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { SmsComposeForm } from "@/components/dashboard/SmsComposeForm";

export const dynamic = "force-dynamic";

const SHIFTS = ["morning", "day", "evening"];

/**
 * Compose a bulk SMS campaign — mirrors `dashboard/sms/compose.blade.php`.
 * Audience selection data is loaded server-side and handed to the client form.
 */
export default async function SmsComposePage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();
  if (!can(user?.role, "bulk_sms")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — Compose SMS
      </p>
    );
  }

  const [classes, sections, staff, students] = await Promise.all([
    prisma.school_classes.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true, shift: true } }).catch(() => []),
    prisma.sections.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }).catch(() => []),
    prisma.users
      .findMany({
        where: { role: { in: ["admin", "teacher", "staff"] }, deleted_at: null },
        select: { id: true, name: true, phone: true, role: true },
        orderBy: { name: "asc" },
      })
      .catch(() => []),
    prisma.students
      .findMany({
        where: { deleted_at: null },
        select: { id: true, user_id: true, phone_1: true, phone_2: true, phone: true, father_phone: true, mother_phone: true, users: { select: { name: true } } },
        orderBy: { id: "asc" },
      })
      .catch(() => []),
  ]);

  return (
    <div>
      <PageHeader
        title="Compose SMS"
        description="Build a campaign and preview the message before sending."
        actions={
          <Link href="/dashboard/sms" className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            ← Bulk SMS
          </Link>
        }
      />

      {sp.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {sp.error === "norecipients" ? "No recipients match the selected audience." : "Please fill in the campaign name and message."}
        </div>
      )}

      <SmsComposeForm
        classes={classes}
        sections={sections}
        shifts={SHIFTS}
        staff={staff.map((s) => ({ id: s.id, name: s.name, phone: s.phone ?? "", role: s.role }))}
        students={students.map((s) => ({
          id: s.id,
          user_id: s.user_id,
          name: s.users?.name ?? `Student #${s.id}`,
          phone: s.phone_1 || s.phone || s.father_phone || s.mother_phone || "",
        }))}
      />
    </div>
  );
}