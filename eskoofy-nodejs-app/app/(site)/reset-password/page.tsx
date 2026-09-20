import Link from "next/link";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; error?: string; done?: string }>;
}) {
  const params = await searchParams;

  return (
    <section className="mx-auto max-w-md px-4 py-12">
      <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-md">
        <h1 className="text-xl font-semibold text-slate-900">Set a new password</h1>
        <p className="mt-1 text-sm text-slate-500">Choose a new password for your account.</p>

        {params.done ? (
          <div className="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            Password updated — you can now sign in.
          </div>
        ) : null}

        {params.error ? (
          <div className="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            Invalid or expired reset token.
          </div>
        ) : null}

        <form action="/reset-password/submit" method="post" className="mt-6 space-y-5">
          <input type="hidden" name="token" value={params.token ?? ""} />
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-slate-700">New password</label>
            <input id="password" name="password" type="password" required minLength={8} autoComplete="new-password"
              className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </div>
          <div>
            <label htmlFor="password_confirmation" className="block text-sm font-medium text-slate-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minLength={8} autoComplete="new-password"
              className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </div>
          <button type="submit" className="w-full rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
            Update password
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          <Link href="/login" className="font-medium text-blue-600 hover:text-blue-700">{t("auth.sign_in")}</Link>
        </p>
      </div>
    </section>
  );
}
