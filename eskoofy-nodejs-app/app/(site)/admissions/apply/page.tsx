import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { Hero, Section } from "@/components/site/Sections";
import { submitAdmission } from "../actions";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

async function loadLookups() {
  try {
    const [sessions, batches] = await Promise.all([
      prisma.academic_sessions.findMany({ orderBy: { id: "desc" }, take: 50 }),
      prisma.batches.findMany({ orderBy: { id: "desc" }, take: 50 }),
    ]);
    return { sessions, batches };
  } catch {
    return { sessions: [], batches: [] };
  }
}

export default async function AdmissionsApplyPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string }>;
}) {
  const params = await searchParams;
  const { sessions, batches } = await loadLookups();
  const ready = sessions.length > 0 && batches.length > 0;

  return (
    <>
      <Hero eyebrow={t("site.nav.admissions")} title={t("site.home.cta_apply")} subtitle="Complete the form below to start the application." />

      <Section>
        <div className="mx-auto max-w-3xl">
          {!ready ? (
            <p className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
              Admissions are not open yet — no academic session or batch is configured.
            </p>
          ) : null}
          {params.error ? (
            <p className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
              Please check the required fields and try again.
            </p>
          ) : null}

          <form action={submitAdmission} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6">
            <fieldset className="grid gap-4 sm:grid-cols-2" disabled={!ready}>
              <legend className="mb-2 text-sm font-bold uppercase tracking-wide text-slate-400">Student</legend>
              <Field id="first_name" label="First name" required />
              <Field id="last_name" label="Last name" required />
              <Select id="gender" label="Gender" options={["male", "female", "other"]} />
              <Field id="date_of_birth" label="Date of birth" type="date" required />
            </fieldset>

            <fieldset className="grid gap-4 sm:grid-cols-2" disabled={!ready}>
              <legend className="mb-2 text-sm font-bold uppercase tracking-wide text-slate-400">Contact</legend>
              <Field id="email" label="Email" type="email" required />
              <Field id="phone" label="Phone" required />
              <Field id="address" label="Address" required />
              <Field id="city" label="City" required />
              <Field id="postal_code" label="Postal code" required />
              <Field id="country" label="Country" defaultValue="Bangladesh" />
            </fieldset>

            <fieldset className="grid gap-4 sm:grid-cols-2" disabled={!ready}>
              <legend className="mb-2 text-sm font-bold uppercase tracking-wide text-slate-400">Guardian</legend>
              <Field id="father_name" label="Father's name" required />
              <Field id="father_phone" label="Father's phone" required />
              <Field id="mother_name" label="Mother's name" required />
              <Field id="mother_phone" label="Mother's phone" required />
            </fieldset>

            <fieldset className="grid gap-4 sm:grid-cols-2" disabled={!ready}>
              <legend className="mb-2 text-sm font-bold uppercase tracking-wide text-slate-400">Placement</legend>
              <div>
                <label htmlFor="academic_session_id" className="mb-1 block text-sm font-semibold">
                  Academic session
                </label>
                <select id="academic_session_id" name="academic_session_id" required className={inputClass}>
                  <option value="">—</option>
                  {sessions.map((session) => (
                    <option key={String(session.id)} value={String(session.id)}>
                      {String(session.name ?? session.id)}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label htmlFor="batch_id" className="mb-1 block text-sm font-semibold">
                  Batch
                </label>
                <select id="batch_id" name="batch_id" required className={inputClass}>
                  <option value="">—</option>
                  {batches.map((batch) => (
                    <option key={String(batch.id)} value={String(batch.id)}>
                      {String(batch.name ?? batch.id)}
                    </option>
                  ))}
                </select>
              </div>
            </fieldset>

            <button
              type="submit"
              disabled={!ready}
              className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-500 disabled:opacity-50"
            >
              {t("site.home.cta_apply")}
            </button>
          </form>
        </div>
      </Section>
    </>
  );
}

function Field({
  id,
  label,
  type = "text",
  required = false,
  defaultValue,
}: {
  id: string;
  label: string;
  type?: string;
  required?: boolean;
  defaultValue?: string;
}) {
  return (
    <div>
      <label htmlFor={id} className="mb-1 block text-sm font-semibold">
        {label}
      </label>
      <input id={id} name={id} type={type} required={required} defaultValue={defaultValue} className={inputClass} />
    </div>
  );
}

function Select({ id, label, options }: { id: string; label: string; options: string[] }) {
  return (
    <div>
      <label htmlFor={id} className="mb-1 block text-sm font-semibold">
        {label}
      </label>
      <select id={id} name={id} className={inputClass}>
        {options.map((option) => (
          <option key={option} value={option}>
            {option}
          </option>
        ))}
      </select>
    </div>
  );
}
