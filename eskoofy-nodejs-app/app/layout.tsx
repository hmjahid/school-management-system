import type { Metadata } from "next";
import { cookies } from "next/headers";
import { resolveRequestLocale, setRequestLocale } from "@/lib/i18n";
import { getSiteSettings } from "@/lib/site-settings";
import "./globals.css";

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getSiteSettings();
  return {
    title: {
      default: `${settings.schoolName} — ${settings.tagline}`,
      template: `%s — ${settings.schoolName}`,
    },
    description: settings.metaDescription,
    icons: {
      icon: "/favicon.ico",
      shortcut: "/favicon.ico",
      apple: "/favicon.ico",
    },
  };
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const store = await cookies();
  const locale = await resolveRequestLocale({ get: (name) => store.get(name)?.value ?? null });
  setRequestLocale(locale);

  return (
    // `suppressHydrationWarning`: the dashboard bootstrap restores the `dark`
    // class on <html> before hydration (mirrors the Laravel layout's no-flash script).
    <html lang={locale} suppressHydrationWarning>
      <head>
        <link rel="icon" href="/favicon.ico" sizes="any" />
        <link rel="apple-touch-icon" href="/favicon.ico" />
        <meta name="theme-color" content="#2563eb" />
      </head>
      <body className="bg-slate-50 text-slate-800 antialiased">{children}</body>
    </html>
  );
}
