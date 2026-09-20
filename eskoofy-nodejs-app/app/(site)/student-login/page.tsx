import { t } from "@/lib/i18n";
import { roleLoginAction } from "../role-login-actions";

export const dynamic = "force-dynamic";

export default async function StudentLoginPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string; redirect?: string }>;
}) {
  const params = await searchParams;
  const action = roleLoginAction("student");

  return (
    <section className="mx-auto flex max-w-md flex-col px-4 py-20">
      <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
        <h1 className="text-2xl font-semibold text-slate-900">Student Login</h1>
        <p className="mt-2 text-sm text-slate-500">Sign in to access your student portal.</p>

        {params.error ? (
          <p className="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {t("auth.invalid")}
          </p>
        ) : null}

        <form action={action} className="mt-6 space-y-5">
          <input type="hidden" name="redirect" value={params.redirect ?? "/dashboard"} />
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-slate-700">{t("auth.email")}</label>
            <input id="email" name="email" type="email" required autoFocus autoComplete="username"
              className="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-slate-700">{t("auth.password")}</label>
            <input id="password" name="password" type="password" required autoComplete="current-password"
              className="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          </div>
          <button type="submit" className="w-full rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">
            {t("auth.sign_in")}
          </button>
        </form>
      </div>
    </section>
  );
}
