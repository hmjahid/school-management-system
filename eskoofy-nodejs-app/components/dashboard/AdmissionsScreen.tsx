import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { Badge, StatusBadge } from "@/components/ui/Badge";
import { PageHeader } from "@/components/ui/PageHeader";
import {
  removeAdmissionTest,
  scheduleAdmissionTest,
  updateAdmissionStatus,
  verifyAdmissionPayment,
} from "@/app/(dashboard)/dashboard/screen-actions";
import { t } from "@/lib/i18n";

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex justify-between gap-4 border-b border-slate-100 py-2 text-sm last:border-0 dark:border-slate-700">
      <dt className="text-slate-500 dark:text-slate-400">{label}</dt>
      <dd className="text-right font-medium text-slate-900 dark:text-slate-100">{value ?? "—"}</dd>
    </div>
  );
}

function fmt(value: unknown): string {
  if (!value) return "—";
  const date = new Date(String(value));
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString();
}

/**
 * Admissions review workflow — mirrors `dashboard/admissions/show.blade.php`:
 * applicant details + payment verification, documents, status update and test
 * scheduling (with test history).
 */
export async function AdmissionReview({ id }: { id: number }) {
  const user = await currentUser();
  const admission = await prisma.admissions.findUnique({
    where: { id },
    include: { admission_documents: true, admission_tests: { orderBy: { scheduled_at: "desc" } } },
  });
  if (!admission) notFound();

  const canEdit = can(user?.role, "manage_students");
  const canVerify = can(user?.role, "manage_payments");

  return (
    <div>
      <PageHeader
        title={`${t("dashboard.admissions")} · ${admission.application_number}`}
        description={`${admission.first_name} ${admission.last_name}`}
        actions={
          <Link href="/dashboard/admissions" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200">
            ← {t("dashboard.admissions")}
          </Link>
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <div className="admin-card">
            <div className="admin-card-header">
              <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Applicant details</h2>
              <StatusBadge value={admission.status} />
            </div>
            <div className="admin-card-body">
              <dl className="grid gap-x-8 sm:grid-cols-2">
                <Row label="Application no." value={admission.application_number} />
                <Row label="Email" value={admission.email} />
                <Row label="Phone" value={admission.phone} />
                <Row label="Gender" value={admission.gender} />
                <Row label="Date of birth" value={fmt(admission.date_of_birth)} />
                <Row label="Blood group" value={admission.blood_group} />
                <Row label="Nationality" value={admission.nationality} />
                <Row label="Religion" value={admission.religion} />
                <Row label="Father" value={`${admission.father_name} (${admission.father_phone})`} />
                <Row label="Mother" value={`${admission.mother_name} (${admission.mother_phone})`} />
                <Row label="Guardian" value={admission.guardian_name} />
                <Row label="Previous school" value={admission.previous_school} />
                <Row label="Address" value={[admission.address, admission.city, admission.state, admission.postal_code].filter(Boolean).join(", ")} />
                <Row label="Submitted" value={fmt(admission.submitted_at)} />
              </dl>
            </div>
          </div>

          <div className="admin-card">
            <div className="admin-card-header">
              <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Payment</h2>
              <StatusBadge value={admission.payment_status} />
            </div>
            <div className="admin-card-body">
              <dl className="grid gap-x-8 sm:grid-cols-2">
                <Row label="Admission fee" value={Number(admission.admission_fee ?? 0).toFixed(2)} />
                <Row label="Payment number" value={admission.payment_number} />
                <Row label="Method" value={admission.payment_method} />
                <Row label="Transaction" value={admission.transaction_id} />
                <Row label="Paid at" value={fmt(admission.paid_at)} />
                <Row label="Verified at" value={fmt(admission.verified_at)} />
              </dl>
              {canVerify && admission.payment_status !== "verified" ? (
                <form action={verifyAdmissionPayment} className="mt-4">
                  <input type="hidden" name="id" value={admission.id} />
                  <button type="submit" className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Verify payment
                  </button>
                </form>
              ) : null}
            </div>
          </div>

          <div className="admin-card">
            <div className="admin-card-header">
              <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Documents</h2>
              <span className="text-xs text-slate-400">{admission.admission_documents.length}</span>
            </div>
            <div className="admin-card-body">
              {admission.admission_documents.length === 0 ? (
                <p className="text-sm text-slate-400">No documents uploaded.</p>
              ) : (
                <ul className="space-y-2">
                  {admission.admission_documents.map((doc) => (
                    <li key={doc.id} className="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2 text-sm dark:border-slate-700">
                      <span className="min-w-0">
                        <span className="block truncate font-medium text-slate-800 dark:text-slate-200">{doc.name}</span>
                        <span className="text-xs text-slate-400">{doc.type}</span>
                      </span>
                      <a href={doc.file_path} target="_blank" rel="noopener noreferrer" className="shrink-0 text-xs font-medium text-brand-600 hover:underline">
                        Open
                      </a>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        </div>

        <div className="space-y-6">
          {canEdit ? (
            <div className="admin-card">
              <div className="admin-card-header">
                <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Update status</h2>
              </div>
              <form action={updateAdmissionStatus} className="admin-card-body space-y-3">
                <input type="hidden" name="id" value={admission.id} />
                <select name="status" defaultValue={admission.status} className="admin-input">
                  {["under_review", "approved", "rejected", "waitlisted", "cancelled"].map((status) => (
                    <option key={status} value={status}>
                      {status.replace(/_/g, " ")}
                    </option>
                  ))}
                </select>
                <textarea name="admission_notes" rows={2} placeholder="Notes" defaultValue={admission.admission_notes ?? ""} className="admin-input" />
                <textarea name="rejection_reason" rows={2} placeholder="Rejection reason (if rejected)" defaultValue={admission.rejection_reason ?? ""} className="admin-input" />
                <button type="submit" className="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                  {t("common.save")}
                </button>
              </form>
            </div>
          ) : null}

          <div className="admin-card">
            <div className="admin-card-header">
              <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Schedule test</h2>
            </div>
            {canEdit ? (
              <form action={scheduleAdmissionTest} className="admin-card-body space-y-3">
                <input type="hidden" name="admission_id" value={admission.id} />
                <input type="datetime-local" name="scheduled_at" required className="admin-input" />
                <input name="venue" placeholder="Venue" className="admin-input" />
                <textarea name="notes" rows={2} placeholder="Notes" className="admin-input" />
                <button type="submit" className="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                  Schedule
                </button>
              </form>
            ) : null}
            <div className="border-t border-slate-100 p-4 dark:border-slate-700">
              <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Test history</h3>
              {admission.admission_tests.length === 0 ? (
                <p className="text-sm text-slate-400">No test scheduled yet.</p>
              ) : (
                <ul className="space-y-2">
                  {admission.admission_tests.map((test) => (
                    <li key={test.id} className="flex items-start justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm dark:border-slate-700">
                      <span>
                        <span className="block text-slate-800 dark:text-slate-200">{fmt(test.scheduled_at)}</span>
                        <span className="text-xs text-slate-400">{test.venue ?? "—"}</span>
                      </span>
                      <span className="flex items-center gap-2">
                        <Badge variant={test.status === "completed" ? "success" : "info"}>{test.status}</Badge>
                        {canEdit ? (
                          <form action={removeAdmissionTest}>
                            <input type="hidden" name="id" value={test.id} />
                            <input type="hidden" name="admission_id" value={admission.id} />
                            <button type="submit" className="text-xs font-medium text-red-600 hover:underline">
                              Remove
                            </button>
                          </form>
                        ) : null}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
