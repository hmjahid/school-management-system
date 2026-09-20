import Link from "next/link";
import { t } from "@/lib/i18n";
import { resetRequestAction } from "../reset-password/actions";

export const dynamic = "force-dynamic";

export default async function ForgotPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ sent?: string; error?: string }>;
}) {
  const params = await searchParams;

  return (
    <section className="mx-auto max-w-md px-4 py-12">
      <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-md">
        <h1 className="text-xl font-semibold text-slate-900">Reset your password</h1>
        <p className="mt-1 text-sm text-slate-500">Enter your email and we will send you a link to reset your password.</p>

        {params.sent ? (
          <div className="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            Reset link sent — check your inbox.
          </div>
        ) : null}

        {params.error ? (
          <div className="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            No account found for that email.
          </div>
        ) : null}

        <form action={resetRequestAction} className="mt-6 space-y-5">
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" required autoFocus autoComplete="username"
              className="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
          </div>
          <button type="submit" className="w-full rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
            Send reset link
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          <Link href="/login" className="font-medium text-blue-600 hover:text-blue-700">{t("auth.sign_in")} / Back to login</Link>
        </p>
      </div>
    </section>
  );
}
