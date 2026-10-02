import { cookies, headers } from "next/headers";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { RevealObserver } from "@/components/site/RevealObserver";
import { resolveRequestLocale, setRequestLocale } from "@/lib/i18n";

export default async function SiteLayout({ children }: { children: React.ReactNode }) {
  const store = await cookies();
  const headerList = await headers();
  const lang = headerList.get("x-lang") ?? undefined;
  const locale = await resolveRequestLocale({
    get: (name) => store.get(name)?.value ?? null,
    searchParams: { lang },
  });
  setRequestLocale(locale);

  return (
    <div className="flex min-h-screen flex-col">
      <RevealObserver />
      <SiteHeader />
      <main className="flex-1">{children}</main>
      <SiteFooter />
    </div>
  );
}
