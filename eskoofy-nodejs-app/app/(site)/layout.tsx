import { cookies } from "next/headers";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { resolveRequestLocale, setRequestLocale } from "@/lib/i18n";

export default async function SiteLayout({ children }: { children: React.ReactNode }) {
  const store = await cookies();
  const locale = await resolveRequestLocale({ get: (name) => store.get(name)?.value ?? null });
  setRequestLocale(locale);

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />
      <main className="flex-1">{children}</main>
      <SiteFooter />
    </div>
  );
}
