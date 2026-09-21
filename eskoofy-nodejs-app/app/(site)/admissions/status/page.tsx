import { prisma } from "@/lib/prisma";
import { t, locale } from "@/lib/i18n";
import { submitPayment } from "../actions";

export const dynamic = "force-dynamic";

const PAYMENT_METHODS = ["bkash", "nagad", "rocket", "bank", "cash"];

const fmt = (value: unknown, pattern: "date" | "datetime") => {
  if (!value) return "—";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "—";
  return pattern === "date"
    ? d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })
    : d.toLocaleString("en-US", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" });
};

export default async function AdmissionStatusPage({
  searchParams,
}: {
  searchParams: Promise<{ application_number?: string; sent?: string; error?: string }>;
}) {
  const params = await searchParams;
  const n = locale();
  const sent = Boolean(params.sent);
  const error = Boolean(params.error);
  const rawNumber = (params.application_number ?? "").trim();
  const applicationNumber = rawNumber.toUpperCase();

  let application: Record<string, unknown> | null = null;
  let settings: Record<string, unknown> | null = null;
  if (applicationNumber) {
    try {
      const rows = await prisma.$queryRaw<Array<{ id: number }>>`
        SELECT id FROM admissions WHERE UPPER(application_number) = ${applicationNumber} LIMIT 1
      `;
      application = rows[0]
        ? ((await prisma.admissions.findUnique({
            where: { id: rows[0].id },
            include: { admission_tests: { orderBy: { scheduled_at: "desc" } } },
          })) as unknown as Record<string, unknown> | null)
        : null;
      settings = (await prisma.admission_settings.findFirst()) as unknown as Record<string, unknown> | null;
    } catch {
      application = null;
    }
  }

  const latestTest = (application?.admission_tests as unknown[])?.find(() => true) as Record<string, unknown> | undefined;
  const admissionFee = Number(application?.admission_fee ?? 0);
  const paymentStatus = String(application?.payment_status ?? "unpaid");
  const appStatus = String(application?.status ?? "");

  const step =
    appStatus === "approved" || appStatus === "enrolled"
      ? 4
      : paymentStatus === "verified" || appStatus === "under_review"
        ? 3
        : paymentStatus === "submitted"
          ? 2
          : paymentStatus === "unpaid" || appStatus === "draft"
            ? 1
            : 0;
  const steps = [
    t("site.admission_steps.applied"),
    t("site.admission_steps.payment"),
    t("site.admission_steps.review"),
    t("site.admission_steps.decision"),
  ];

  const paymentInstructions =
    n === "bn" && settings?.payment_instructions_bn
      ? String(settings.payment_instructions_bn)
      : (settings?.payment_instructions_en ? String(settings.payment_instructions_en) : null);

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.admission_status.hero_title")}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{t("site.admission_status.hero_subtitle")}</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <form method="get" action="/admissions/status" className="flex max-w-xl flex-wrap gap-3">
          <label className="sr-only" htmlFor="application_number">
            {t("site.admission_status.application_number")}
          </label>
          <input
            type="text"
            id="application_number"
            name="application_number"
            defaultValue={applicationNumber}
            placeholder={t("site.admission_status.placeholder")}
            className="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
          <button type="submit" className="rounded-md bg-orange-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-orange-600">
            {t("site.admission_status.look_up")}
          </button>
        </form>

        {applicationNumber ? (
          <div className="mt-10 rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-md">
            {sent ? (
              <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                Payment details submitted. Verification pending.
              </p>
            ) : null}
            {error ? (
              <p className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                Please check the form and try again.
              </p>
            ) : null}
            {application ? (
              <>
                <div className="mb-6 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                  <svg className="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                  </svg>
                  <p>
                    {t("site.admission_steps.save_number", { num: String(application.application_number ?? "") })}
                    <span className="block font-mono text-sm">{String(application.application_number ?? "")}</span>
                  </p>
                </div>

                <ol className="mb-6 grid grid-cols-4 gap-2">
                  {steps.map((label, i) => {
                    const reached = i + 1 <= step;
                    const current = i + 1 === step + 1 && step < 4;
                    return (
                      <li key={label} className="text-center">
                        <div
                          className={`mx-auto flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ${
                            reached ? "bg-emerald-600 text-white" : current ? "bg-blue-600 text-white ring-4 ring-blue-100" : "bg-gray-200 text-gray-500"
                          }`}
                        >
                          {i + 1}
                        </div>
                        <div className={`mt-1 text-xs font-medium ${reached ? "text-emerald-700" : "text-gray-500"}`}>{label}</div>
                      </li>
                    );
                  })}
                </ol>

                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-gray-500">{t("site.admission_status.application_number")}</div>
                    <div className="font-mono text-lg text-gray-900">{String(application.application_number ?? "")}</div>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <a
                      href={`/admissions/${String(application.id ?? "")}/receipt`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                    >
                      Download receipt
                    </a>
                    {paymentStatus === "verified" && appStatus === "approved" ? (
                      <a
                        href={`/admissions/${String(application.id ?? "")}/approval-letter`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                      >
                        Download approval letter
                      </a>
                    ) : null}
                  </div>
                </div>

                <dl className="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                  <dt className="font-medium text-gray-500">{t("site.admission_status.applicant")}</dt>
                  <dd className="text-gray-900">
                    {String(application.first_name ?? "")} {String(application.last_name ?? "")}
                  </dd>
                  <dt className="font-medium text-gray-500">{t("site.admission_status.status")}</dt>
                  <dd>
                    <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold capitalize text-blue-800">{appStatus || "draft"}</span>
                  </dd>
                  <dt className="font-medium text-gray-500">Admission fee</dt>
                  <dd className="text-gray-900">৳ {admissionFee.toFixed(2)}</dd>
                  <dt className="font-medium text-gray-500">Payment status</dt>
                  <dd>
                    <span
                      className={`rounded-full px-2 py-1 text-xs font-semibold ${
                        paymentStatus === "verified"
                          ? "bg-emerald-100 text-emerald-800"
                          : paymentStatus === "submitted"
                            ? "bg-blue-100 text-blue-800"
                            : paymentStatus === "rejected"
                              ? "bg-red-100 text-red-800"
                              : "bg-amber-100 text-amber-800"
                      }`}
                    >
                      {paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1)}
                    </span>
                  </dd>
                  <dt className="font-medium text-gray-500">{t("site.admission_status.submitted")}</dt>
                  <dd className="text-gray-900">{fmt(application.submitted_at ?? application.created_at, "datetime")}</dd>
                </dl>

                {latestTest && latestTest.scheduled_at ? (
                  <div className="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    <div className="font-semibold">{t("site.admission_status.test_scheduled")}</div>
                    <div className="mt-1">
                      {t("site.admission_status.date")}: <span className="font-medium">{fmt(latestTest.scheduled_at, "datetime")}</span>
                    </div>
                    {latestTest.venue ? (
                      <div>
                        {t("site.admission_status.venue")}: <span className="font-medium">{String(latestTest.venue)}</span>
                      </div>
                    ) : null}
                    {latestTest.notes ? <div className="mt-1 text-blue-800">{String(latestTest.notes)}</div> : null}
                  </div>
                ) : null}

                {paymentStatus === "unpaid" && admissionFee > 0 ? (
                  <div className="mt-8 rounded-lg border border-amber-300 bg-amber-50 p-5">
                    <h3 className="text-sm font-semibold text-amber-900">Submit payment details</h3>
                    <p className="mt-1 text-xs text-amber-800">
                      {paymentInstructions ?? "Send the admission fee and submit your transaction ID below."}
                    </p>
                    {application.payment_number ? (
                      <p className="mt-2 text-xs text-amber-900">
                        Payment number: <span className="font-mono font-semibold">{String(application.payment_number)}</span>
                      </p>
                    ) : null}
                    <form action={submitPayment} className="mt-4 grid gap-3 sm:grid-cols-3">
                      <input type="hidden" name="id" value={String(application.id ?? "")} />
                      <div>
                        <label className="block text-xs font-semibold text-amber-900">Method</label>
                        <select name="payment_method" required defaultValue="" className="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
                          <option value="" disabled>
                            —
                          </option>
                          {PAYMENT_METHODS.map((m) => (
                            <option key={m} value={m}>
                              {m.toUpperCase()}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="sm:col-span-2">
                        <label className="block text-xs font-semibold text-amber-900">Transaction ID</label>
                        <input type="text" name="transaction_id" required maxLength={128} className="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 font-mono text-sm" />
                      </div>
                      <div className="flex justify-end sm:col-span-3">
                        <button className="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Submit payment</button>
                      </div>
                    </form>
                  </div>
                ) : paymentStatus === "submitted" ? (
                  <div className="mt-8 rounded-lg border border-blue-300 bg-blue-50 p-5 text-sm text-blue-900">
                    <div className="font-semibold">Awaiting payment verification</div>
                    <p className="mt-1">
                      Your transaction ID <span className="font-mono">{String(application.transaction_id ?? "")}</span> has been submitted. We will verify
                      and email you once confirmed.
                    </p>
                  </div>
                ) : paymentStatus === "rejected" ? (
                  <div className="mt-8 rounded-lg border border-red-300 bg-red-50 p-5 text-sm text-red-900">
                    <div className="font-semibold">Payment could not be verified</div>
                    {application.payment_note ? <p className="mt-1">{String(application.payment_note)}</p> : null}
                    <p className="mt-1">Please contact the school office or submit a new transaction ID.</p>
                    <form action={submitPayment} className="mt-4 grid gap-3 sm:grid-cols-3">
                      <input type="hidden" name="id" value={String(application.id ?? "")} />
                      <div>
                        <select name="payment_method" required className="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm">
                          <option value="" disabled>
                            —
                          </option>
                          {PAYMENT_METHODS.map((m) => (
                            <option key={m} value={m}>
                              {m.toUpperCase()}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="sm:col-span-2">
                        <input type="text" name="transaction_id" required maxLength={128} placeholder="New transaction ID" className="w-full rounded-lg border border-red-300 bg-white px-3 py-2 font-mono text-sm" />
                      </div>
                      <div className="flex justify-end sm:col-span-3">
                        <button className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Resubmit</button>
                      </div>
                    </form>
                  </div>
                ) : paymentStatus === "verified" && appStatus !== "approved" ? (
                  <div className="mt-8 rounded-lg border border-emerald-300 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <div className="font-semibold">Payment verified</div>
                    <p className="mt-1">Your payment has been verified successfully. Your application is now being reviewed. We will notify you once a decision is made.</p>
                  </div>
                ) : null}

                {application.rejection_reason ? (
                  <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{String(application.rejection_reason)}</p>
                ) : null}

                <p className="mt-6 text-sm text-gray-600">{t("site.admission_status.follow_up")}</p>
              </>
            ) : (
              <p className="text-gray-600">{t("site.admission_status.not_found")}</p>
            )}
          </div>
        ) : null}
      </div>
    </div>
  );
}