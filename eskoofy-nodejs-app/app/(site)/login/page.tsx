import { t } from "@/lib/i18n";
import { loginAction } from "./actions";

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string; redirect?: string }>;
}) {
  const params = await searchParams;

  return (
    <section className="mx-auto flex max-w-md flex-col px-4 py-20">
      <h1 className="text-2xl font-bold text-slate-900">{t("auth.login_title")}</h1>
      <p className="mt-2 text-sm text-slate-500">{t("brand.tagline")}</p>

      {params.error ? (
        <p className="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
          {t("auth.invalid")}
        </p>
      ) : null}

      <form action={loginAction} className="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
        <input type="hidden" name="redirect" value={params.redirect ?? "/dashboard"} />

        <div>
          <label htmlFor="email" className="mb-1 block text-sm font-semibold">
            {t("auth.email")}
          </label>
          <input
            id="email"
            name="email"
            type="email"
            required
            autoComplete="email"
            className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label htmlFor="password" className="mb-1 block text-sm font-semibold">
            {t("auth.password")}
          </label>
          <input
            id="password"
            name="password"
            type="password"
            required
            autoComplete="current-password"
            className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <button
          type="submit"
          className="w-full rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-500"
        >
          {t("auth.sign_in")}
        </button>
      </form>
    </section>
  );
}
