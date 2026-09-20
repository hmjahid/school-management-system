import Link from "next/link";
import type { ReactNode } from "react";

export function Section({
  id,
  title,
  intro,
  action,
  children,
  tone = "light",
}: {
  id?: string;
  title?: ReactNode;
  intro?: ReactNode;
  action?: { label: string; href: string };
  children: ReactNode;
  tone?: "light" | "muted" | "dark";
}) {
  const bg = tone === "dark" ? "bg-slate-900 text-white" : tone === "muted" ? "bg-slate-50" : "";
  return (
    <section id={id} className={`${bg} py-16`}>
      <div className="mx-auto max-w-7xl px-4">
        {title ? (
          <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
              <h2 className="text-2xl font-bold">{title}</h2>
              {intro ? <p className={`mt-2 max-w-2xl text-sm ${tone === "dark" ? "text-slate-300" : "text-slate-500"}`}>{intro}</p> : null}
            </div>
            {action ? (
              <Link href={action.href} className="text-sm font-semibold text-blue-600 hover:underline">
                {action.label} →
              </Link>
            ) : null}
          </div>
        ) : null}
        {children}
      </div>
    </section>
  );
}

export function Hero({
  eyebrow,
  title,
  subtitle,
  primary,
  secondary,
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  primary?: { label: string; href: string };
  secondary?: { label: string; href: string };
}) {
  return (
    <section className="bg-gradient-to-br from-slate-900 via-blue-700 to-blue-600 text-white">
      <div className="mx-auto max-w-7xl px-4 py-16 text-center">
        {eyebrow ? <span className="inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-medium">{eyebrow}</span> : null}
        <h1 className="mx-auto mt-5 max-w-3xl text-3xl font-extrabold leading-tight md:text-4xl">{title}</h1>
        {subtitle ? <p className="mx-auto mt-4 max-w-2xl text-slate-200">{subtitle}</p> : null}
        {(primary || secondary) && (
          <div className="mt-8 flex flex-wrap justify-center gap-3">
            {primary ? (
              <Link href={primary.href} className="rounded-xl bg-white px-6 py-3 font-semibold text-blue-700 hover:bg-slate-100">
                {primary.label}
              </Link>
            ) : null}
            {secondary ? (
              <Link href={secondary.href} className="rounded-xl border border-white/30 px-6 py-3 font-semibold text-white hover:border-white">
                {secondary.label}
              </Link>
            ) : null}
          </div>
        )}
      </div>
    </section>
  );
}

/** Gradient page banner (mirrors the app's `site/*.blade.php` heroes). */
export function PageHero({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
      <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <h1 className="text-4xl font-bold md:text-5xl">{title}</h1>
        {subtitle ? <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{subtitle}</p> : null}
      </div>
    </div>
  );
}

export function CardGrid({ children, columns = 3 }: { children: ReactNode; columns?: 2 | 3 | 4 }) {
  const cols = columns === 2 ? "sm:grid-cols-2" : columns === 4 ? "sm:grid-cols-2 lg:grid-cols-4" : "sm:grid-cols-2 lg:grid-cols-3";
  return <div className={`grid gap-4 ${cols}`}>{children}</div>;
}

export function InfoCard({
  title,
  subtitle,
  href,
  body,
  meta,
}: {
  title: ReactNode;
  subtitle?: ReactNode;
  href?: string;
  body?: ReactNode;
  meta?: ReactNode;
}) {
  const inner = (
    <div className="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-md">
      {meta ? <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600">{meta}</div> : null}
      <h3 className="font-semibold text-slate-900">{title}</h3>
      {subtitle ? <p className="mt-1 text-xs text-slate-400">{subtitle}</p> : null}
      {body ? <p className="mt-3 text-sm text-slate-600">{body}</p> : null}
    </div>
  );
  return href ? <Link href={href}>{inner}</Link> : inner;
}

export function StatBar({ stats }: { stats: Array<{ label: string; value: number | string }> }) {
  return (
    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
      {stats.map((stat) => (
        <div key={stat.label} className="rounded-2xl border border-slate-200 bg-white p-5 text-center">
          <div className="text-3xl font-extrabold text-slate-900">{stat.value}</div>
          <div className="mt-1 text-xs uppercase tracking-wide text-slate-500">{stat.label}</div>
        </div>
      ))}
    </div>
  );
}

export function Empty({ children }: { children: ReactNode }) {
  return <div className="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400">{children}</div>;
}

export function formatDate(value: unknown): string {
  if (!value) return "";
  const date = value instanceof Date ? value : new Date(String(value));
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toISOString().slice(0, 10);
}
