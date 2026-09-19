import type { Metadata } from "next";
import { eskoolfy } from "@/config/eskoolfy";
import { t } from "@/lib/i18n";
import "./globals.css";

export const metadata: Metadata = {
  title: `${t("brand.name")} — ${t("brand.tagline")}`,
  description: t("home.subtitle"),
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang={eskoolfy.locale}>
      <body className="bg-slate-50 text-slate-800 antialiased">{children}</body>
    </html>
  );
}
