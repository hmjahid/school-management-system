import Link from "next/link";
import type { Metadata } from "next";
import { locale, t } from "@/lib/i18n";
import {
  getAdmissionsOpen,
  getCommittee,
  getHomeContent,
  getLatestNews,
  getRecentNotices,
  getSchoolStats,
  getSectionVisibility,
  getSliderFallback,
  getStudentsNotable,
  getTeachers,
  getUpcomingEvents,
} from "@/lib/site-data";
import { CardGrid, Empty, formatDate } from "@/components/site/Sections";
import { getSiteSettings, splitSchoolName } from "@/lib/site-settings";
import { HOME_DEFAULTS } from "@/lib/site-cms-defaults";
import { CardSlider, CountUp, NoticeScroller, initialsOf } from "@/components/site/HomeInteractive";
import type { Row } from "@/lib/site-data";

export const dynamic = "force-dynamic";

type Heading = { title?: unknown; intro?: unknown; view_all?: unknown };

function heading(h: unknown, dtitle: string, dintro?: string, dview?: string): { title: string; intro?: string; view_all?: string } {
  const obj = (h && typeof h === "object" ? h : {}) as Heading;
  return {
    title: String(obj.title ?? dtitle),
    intro: obj.intro !== undefined && obj.intro !== null && obj.intro !== "" ? String(obj.intro) : dintro,
    view_all: String(obj.view_all ?? dview ?? ""),
  };
}

function asArray(value: unknown): Row[] {
  return Array.isArray(value) ? (value as Row[]) : [];
}

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getSiteSettings();
  const homeContent = await getHomeContent(locale());
  return {
    title: `${String(homeContent?.title ?? t("site.nav.home"))} — ${settings.schoolName}`,
    description: String(homeContent?.meta_description ?? settings.metaDescription),
  };
}

export default async function HomePage() {
  const [settings, homeContent, stats, teachers, committee, notableStudents, news, recentNotices, upcomingEvents, sliderFallback, admissionsOpen, sectionVis] = await Promise.all([
    getSiteSettings(),
    getHomeContent(locale()),
    getSchoolStats(),
    getTeachers(8),
    getCommittee(20),
    getStudentsNotable(8),
    getLatestNews(6),
    getRecentNotices(5),
    getUpcomingEvents(6),
    getSliderFallback(6),
    getAdmissionsOpen(),
    getSectionVisibility(),
  ]);

  const hc = (homeContent?.content && typeof homeContent.content === "object" ? (homeContent.content as Record<string, unknown>) : {}) as Record<string, unknown>;

  const hero = (hc.hero && typeof hc.hero === "object" ? hc.hero : {}) as Record<string, unknown>;
  const principal = (hc.principal && typeof hc.principal === "object" ? hc.principal : {}) as Record<string, unknown>;
  const featuresContent = asArray(hc.features);
  const highlightsContent = asArray(hc.highlights);
  const testimonialsContent = asArray(hc.testimonials);
  const partnersContent = asArray(hc.partners);

  const defaults = HOME_DEFAULTS[locale()] ?? HOME_DEFAULTS.en;

  const features = featuresContent.length > 0 ? featuresContent : defaults.features.map((f) => ({ title: f.title, description: f.description }));
  const highlights = highlightsContent.length > 0 ? highlightsContent.map((h) => String(h)) : defaults.highlights;
  const testimonialsFallback = testimonialsContent.length > 0 ? testimonialsContent : defaults.testimonials.map((tItem) => ({ quote: tItem.quote, name: tItem.name, role: tItem.role }));

  const principalMessage = String(principal.message ?? t("site.home.principal_message_default"));

  const featuresH = heading(hc.features_heading, t("site.home.features_title"), t("site.home.features_intro"));
  const teachersH = heading(hc.teachers, t("site.home.teachers_title"), t("site.home.teachers_intro"), t("site.home.teachers_view_all"));
  const committeeH = heading(hc.committee_members, t("site.home.committee_title"), t("site.home.committee_intro"), t("site.home.committee_view_all"));
  const testimonialsH = heading(hc.testimonials_heading, t("site.home.testimonials_title"));
  const remarkableH = heading(hc.remarkable_students, t("site.home.remarkable_students_title"), t("site.home.remarkable_students_intro"));
  const eventsH = heading(hc.events, t("site.home.events_title"), undefined, t("site.home.events_view_all"));
  const newsH = heading(hc.news, t("site.home.news_title"), undefined, t("site.home.news_view_all"));
  const highlightsH = heading(hc.highlights_heading, t("site.home.highlights_title"));
  const partnersH = heading(hc.partners_heading, t("site.home.our_partners"));

  const heroImg = String(hero.background_image ?? "");
  const headline = String(hero.headline ?? t("site.home.hero_headline"));
  const sub = String(hero.motto ?? hero.subtitle ?? t("site.home.hero_subtitle"));
  const heroCtaPrimary = String(hero.cta_primary ?? t("site.home.hero_cta_primary"));
  const heroCtaSecondary = String(hero.cta_secondary ?? t("site.home.hero_cta_secondary"));
  const heroDesign = String(hero.hero_design ?? "design-1");

  const sliderSlidesRaw = asArray(hc.slider);
  const sliderSlides = sliderSlidesRaw.length > 0 ? sliderSlidesRaw : sliderFallback;

  const teacherRows = teachers as Row[];
  const committeeRows = committee as Row[];
  const notableRows = notableStudents as Row[];
  const newsRows = news as Row[];
  const noticeRows = recentNotices as Row[];
  const eventRows = upcomingEvents as Row[];

  const vis = (key: string) => sectionVis[key] !== false;

  const { first, rest } = splitSchoolName(settings.schoolName);

  return (
    <>
      {vis("hero") && (
        <section className="relative flex min-h-[85vh] items-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950">
          {heroImg ? (
            <>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={heroImg} alt="" className="absolute inset-0 h-full w-full object-cover" width={1920} height={1080} />
              <div className="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-800/80 to-indigo-950/90" />
            </>
          ) : null}

          <div className="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-orange-500/10 blur-3xl" aria-hidden="true" />
          <div className="absolute -bottom-40 -left-40 h-[500px] w-[500px] rounded-full bg-indigo-500/10 blur-3xl" aria-hidden="true" />

          <div className="absolute inset-x-0 top-0 z-10" aria-hidden="true">
            <div className="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
              <div className="mx-auto max-w-3xl rounded-2xl border border-white/10 bg-white/[0.06] px-8 py-4 text-center shadow-xl shadow-black/10 backdrop-blur-md">
                <span className="text-xl font-black uppercase tracking-widest text-white drop-shadow-lg sm:text-2xl lg:text-3xl">
                  {first}
                  {rest ? ` ${rest}` : ""}
                </span>
              </div>
            </div>
          </div>

          <div className="relative z-10 mx-auto w-full max-w-7xl px-4 pb-20 pt-28 sm:px-6 lg:px-8">
            <div className="grid gap-10 lg:grid-cols-12 lg:items-center">
              <div className="lg:col-span-7 xl:col-span-7">
                <h1 className="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl" style={{ textShadow: "0 4px 30px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3)" }}>
                  {headline}
                </h1>
                <p className="mt-6 max-w-2xl text-lg leading-relaxed text-white/90 sm:text-xl">{sub}</p>
                <div className="mt-10 flex flex-wrap items-center gap-4">
                  {admissionsOpen ? (
                    <Link href="/admissions/apply" className="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-orange-600 hover:shadow-xl">
                      {heroCtaPrimary}
                      <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </Link>
                  ) : (
                    <Link href="/contact" className="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-orange-600 hover:shadow-xl">
                      {t("site.home.cta_contact")}
                      <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </Link>
                  )}
                  <Link href="/about" className="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:border-white/40 hover:bg-white/10">
                    {heroCtaSecondary}
                  </Link>
                </div>
              </div>

              {vis("urgent_notices") && noticeRows.length > 0 ? (
                <div className="lg:col-span-5 xl:col-span-5">
                  <NoticeScroller
                    notices={noticeRows.map((n) => ({
                      id: n.id,
                      title: n.title_bn && locale() === "bn" ? n.title_bn : n.title,
                      content: n.content_bn && locale() === "bn" ? n.content_bn : n.content,
                      pinned: n.pinned,
                    }))}
                    noticesTitle={t("site.home.latest_notices")}
                    viewAllHref="/notices"
                    viewAllLabel={t("site.home.view_all_notices")}
                  />
                </div>
              ) : null}
            </div>
          </div>
        </section>
      )}

      {vis("features") && (
        <section className="bg-white py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-14 text-center">
              <h2 className="mb-4 text-4xl font-bold text-gray-900">{featuresH.title}</h2>
              <div className="mx-auto h-1 w-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
              {featuresH.intro ? <p className="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{featuresH.intro}</p> : null}
            </div>
            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
              {features.map((feature, index) => (
                <div key={index} className="group rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-blue-100">
                  <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 text-2xl font-bold text-blue-600 transition-colors group-hover:from-blue-100 group-hover:to-indigo-100">
                    {index + 1}
                  </div>
                  <h3 className="mb-3 text-center text-xl font-semibold text-gray-900">{String(feature.title ?? "")}</h3>
                  <p className="text-center leading-relaxed text-gray-600">{String(feature.description ?? "")}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {vis("stats") && (
        <section className="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 py-20 text-white">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-2 gap-8 text-center md:grid-cols-4">
              <div className="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <CountUp target={stats.students} suffix="+" />
                <div className="mt-2 text-sm uppercase tracking-wider text-blue-200">{t("site.home.stats_students")}</div>
              </div>
              <div className="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <CountUp target={stats.teachers} suffix="+" />
                <div className="mt-2 text-sm uppercase tracking-wider text-blue-200">{t("site.home.stats_faculty")}</div>
              </div>
              <div className="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <CountUp target={stats.years ?? 0} suffix="+" />
                <div className="mt-2 text-sm uppercase tracking-wider text-blue-200">{t("site.home.stats_years")}</div>
              </div>
              <div className="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <CountUp target={stats.awards} suffix="+" />
                <div className="mt-2 text-sm uppercase tracking-wider text-blue-200">{t("site.home.stats_awards")}</div>
              </div>
            </div>
          </div>
        </section>
      )}

      {vis("principal") && principalMessage && (
        <section className="bg-white py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-12 text-center">
              <h2 className="text-3xl font-bold text-gray-900">{String(principal.section_title ?? t("site.home.principal_title"))}</h2>
              <div className="mx-auto mt-3 h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
            </div>
            <div className="grid items-center gap-12 lg:grid-cols-5 lg:gap-16">
              <div className="lg:col-span-2">
                <div className="relative mx-auto w-full max-w-xs">
                  {principal.photo ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={String(principal.photo)} alt={String(principal.name ?? t("site.home.principal_fallback"))} className="aspect-[4/3] w-full rounded-2xl object-cover object-top shadow-xl ring-1 ring-slate-200" />
                  ) : (
                    <div className="flex aspect-[4/3] w-full items-center justify-center rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100">
                      <svg className="h-20 w-20 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" /></svg>
                    </div>
                  )}
                  {principal.name ? (
                    <div className="absolute -bottom-5 left-1/2 w-max max-w-full -translate-x-1/2 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-lg">
                      {String(principal.name)}
                      {principal.designation ? <span className="font-normal text-orange-100"> · {String(principal.designation)}</span> : null}
                    </div>
                  ) : null}
                </div>
              </div>
              <div className="lg:col-span-3">
                <div className="relative rounded-2xl border border-orange-100 bg-orange-50/50 p-8 sm:p-10">
                  <svg className="absolute -left-4 -top-5 h-12 w-12 text-orange-300" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z" /></svg>
                  <blockquote className="mt-2 text-lg leading-relaxed text-gray-700">{principalMessage}</blockquote>
                  <div className="mt-6 flex items-center gap-3">
                    <div className="h-1 w-10 rounded-full bg-orange-400" />
                    <p className="text-sm font-medium uppercase tracking-wide text-gray-500">{String(principal.designation ?? t("site.home.principal_fallback"))}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      )}

      {vis("teachers") && teacherRows.length > 0 && (
        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-14 text-center">
              <h2 className="mb-4 text-4xl font-bold text-gray-900">{teachersH.title}</h2>
              <div className="mx-auto h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
              {teachersH.intro ? <p className="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{teachersH.intro}</p> : null}
            </div>
            <CardSlider hint={t("site.home.swipe_hint")}>
              {teacherRows.map((teacher) => {
                const user = (teacher.users && typeof teacher.users === "object" ? teacher.users : {}) as Row;
                const name = String(user.name ?? t("site.home.teacher_fallback"));
                return (
                  <div key={String(teacher.id)} className="group w-full min-w-0 shrink-0 snap-center rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                    <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 shadow-lg ring-4 ring-white transition-transform duration-300 group-hover:scale-105">
                      {initialsOf(name)}
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-gray-900">{name}</h3>
                    <p className="mt-1 text-sm text-gray-500">{String(teacher.qualification ?? t("site.home.teacher_fallback"))}</p>
                  </div>
                );
              })}
            </CardSlider>
            {teachersH.view_all ? (
              <div className="mt-10 text-center">
                <Link href="/faculty" className="inline-flex items-center gap-1.5 font-medium text-blue-600 transition-colors hover:text-blue-800">
                  {teachersH.view_all}
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </Link>
              </div>
            ) : null}
          </div>
        </section>
      )}

      {vis("committee_members") && (
        <section className="bg-white py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-14 text-center">
              <h2 className="mb-4 text-4xl font-bold text-gray-900">{committeeH.title}</h2>
              <div className="mx-auto h-1 w-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
              {committeeH.intro ? <p className="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{committeeH.intro}</p> : null}
            </div>
            {committeeRows.length > 0 ? (
              <CardSlider>
                {committeeRows.map((member) => {
                  const name = String((locale() === "bn" && member.name_bn) ? member.name_bn : member.name ?? "");
                  const designation = String((locale() === "bn" && member.designation_bn) ? member.designation_bn : member.designation ?? "");
                  const photo = String(member.photo ?? "");
                  return (
                    <div key={String(member.id)} className="group w-full min-w-0 shrink-0 snap-start rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                      <div className="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 shadow-lg ring-4 ring-white transition-transform duration-300 group-hover:scale-105">
                        {photo ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={photo} alt={name} className="h-full w-full object-cover" />
                        ) : (
                          initialsOf(name)
                        )}
                      </div>
                      <h3 className="mt-4 text-lg font-semibold text-gray-900">{name}</h3>
                      <p className="mt-1 text-sm font-medium text-blue-600">{designation}</p>
                      {member.phone ? (
                        <p className="mt-2 flex items-center justify-center gap-1 text-xs text-gray-400">
                          <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                          {String(member.phone)}
                        </p>
                      ) : null}
                    </div>
                  );
                })}
              </CardSlider>
            ) : (
              <div className="py-12 text-center">
                <svg className="mx-auto h-16 w-16 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" /></svg>
                <p className="mt-4 text-gray-500">{t("site.home.committee_empty")}</p>
              </div>
            )}
            {committeeH.view_all ? (
              <div className="mt-10 text-center">
                <Link href="/committee" className="inline-flex items-center gap-1.5 font-medium text-blue-600 transition-colors hover:text-blue-800">
                  {committeeH.view_all}
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </Link>
              </div>
            ) : null}
          </div>
        </section>
      )}

      {vis("testimonials") && testimonialsFallback.length > 0 && (
        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-14 text-center">
              <h2 className="mb-4 text-4xl font-bold text-gray-900">{testimonialsH.title}</h2>
              <div className="mx-auto h-1 w-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
            </div>
            <div className="mx-auto grid max-w-5xl gap-8 md:grid-cols-2">
              {testimonialsFallback.map((tItem, index) => (
                <div key={index} className="relative rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100">
                  <svg className="absolute left-6 top-6 h-10 w-10 text-orange-200" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z" /></svg>
                  <p className="relative z-10 mb-6 mt-4 text-lg leading-relaxed text-gray-700">{String(tItem.quote ?? "")}</p>
                  <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-lg font-bold text-blue-600">
                      {String(tItem.name ?? "A").charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900">{String(tItem.name ?? "")}</h4>
                      <p className="text-sm text-gray-500">{String(tItem.role ?? "")}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {vis("remarkable_students") && notableRows.length > 0 && (
        <section className="bg-white py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-14 text-center">
              <h2 className="mb-4 text-4xl font-bold text-gray-900">{remarkableH.title}</h2>
              <div className="mx-auto h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
              {remarkableH.intro ? <p className="mx-auto mt-4 max-w-3xl text-lg text-gray-600">{remarkableH.intro}</p> : null}
            </div>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {notableRows.map((student) => {
                const user = (student.users && typeof student.users === "object" ? student.users : {}) as Row;
                const klass = (student.school_classes && typeof student.school_classes === "object" ? student.school_classes : null) as Row | null;
                const name = String(user.name ?? ((student.first_name && student.last_name) ? `${student.first_name} ${student.last_name}` : t("site.home.student_fallback")));
                return (
                  <div key={String(student.id)} className="group rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-orange-100 text-2xl font-bold text-orange-600 shadow-lg ring-4 ring-white transition-transform duration-300 group-hover:scale-105">
                      {initialsOf(name)}
                    </div>
                    <h3 className="mt-4 text-lg font-semibold text-gray-900">{name}</h3>
                    {klass?.name ? <p className="mt-1 text-sm text-gray-500">{String(klass.name)}</p> : null}
                    {student.achievement ? <p className="mt-2 text-xs font-medium leading-relaxed text-orange-600">{String(student.achievement)}</p> : null}
                  </div>
                );
              })}
            </div>
          </div>
        </section>
      )}

      {vis("slider") && sliderSlides.length > 0 && (
        <section className="bg-slate-100 py-16">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
              <div>
                <h2 className="text-3xl font-bold text-gray-900">{t("site.home.slider_title")}</h2>
                <div className="mt-2 h-1 w-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
                <p className="mt-3 max-w-2xl text-gray-600">{t("site.home.slider_intro")}</p>
              </div>
            </div>
            <CardSlider>
              {sliderSlides.map((slide, index) => {
                const img = String(slide.image ?? slide.image_path ?? slide.image_url ?? "");
                if (!img) return null;
                const slideTitle = String(slide.title ?? "");
                const caption = String(slide.caption ?? "");
                const link = String(slide.link ?? "");
                return (
                  <div key={String(slide.id ?? index)} className="group relative w-full min-w-0 shrink-0 snap-start overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 md:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]">
                    <div className="h-60 overflow-hidden bg-slate-200">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={img} alt={slideTitle} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                    </div>
                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />
                    <div className="absolute bottom-0 w-full p-5">
                      {slideTitle ? <h3 className="text-lg font-bold text-white">{slideTitle}</h3> : null}
                      {caption ? <p className="mt-1 text-sm text-white/80">{caption}</p> : null}
                    </div>
                    {link ? <Link href={link} className="absolute inset-0" aria-label={slideTitle} /> : null}
                  </div>
                );
              })}
            </CardSlider>
          </div>
        </section>
      )}

      {vis("events") && eventRows.length > 0 && (
        <section className="bg-white py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
              <div>
                <h2 className="text-3xl font-bold text-gray-900">{eventsH.title}</h2>
                <div className="mt-2 h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
              </div>
              {eventsH.view_all ? (
                <Link href="/events" className="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">
                  {eventsH.view_all}
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </Link>
              ) : null}
            </div>
            <div className="grid gap-6 md:grid-cols-3">
              {eventRows.map((ev) => (
                <div key={String(ev.id)} className="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl">
                  <div className="inline-flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <time dateTime={ev.start_date ? new Date(String(ev.start_date)).toISOString() : ""}>{formatDate(ev.start_date)}</time>
                  </div>
                  <h3 className="mt-4 text-lg font-semibold text-gray-900 transition-colors group-hover:text-blue-600">{String(ev.title ?? "")}</h3>
                  {ev.location ? (
                    <p className="mt-2 flex items-center gap-1.5 text-sm text-gray-500">
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                      {String(ev.location)}
                    </p>
                  ) : null}
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {vis("news") && newsRows.length > 0 && (
        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
              <div>
                <h2 className="text-3xl font-bold text-gray-900">{newsH.title}</h2>
                <div className="mt-2 h-1 w-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
              </div>
              {newsH.view_all ? (
                <Link href="/news" className="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">
                  {newsH.view_all}
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </Link>
              ) : null}
            </div>
            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
              {newsRows.map((item) => (
                <div key={String(item.id)} className="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:shadow-xl">
                  <div className="relative h-52 overflow-hidden bg-slate-200">
                    {item.image_url ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={String(item.image_url)} alt="" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                    ) : (
                      <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-100">
                        <svg className="h-12 w-12 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" /></svg>
                      </div>
                    )}
                    <div className="absolute left-4 top-4">
                      <span className="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{String(item.category ?? t("site.home.news_badge"))}</span>
                    </div>
                  </div>
                  <div className="p-6">
                    {item.published_at ? <div className="mb-2 text-sm text-slate-500">{formatDate(item.published_at)}</div> : null}
                    <h3 className="mb-3 text-xl font-semibold text-gray-900 transition-colors group-hover:text-blue-600">{String(item.title ?? "")}</h3>
                    <p className="mb-4 leading-relaxed text-slate-600">{String(item.content ?? "").slice(0, 140)}</p>
                    <Link href={`/news/${String(item.slug)}`} className="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">
                      {t("site.home.read_more")}
                      <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {vis("highlights") && highlights.length > 0 && (
        <section className="bg-white py-16">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 className="mb-4 text-center text-2xl font-bold text-gray-900">{highlightsH.title}</h2>
            <div className="mx-auto mb-8 h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
            <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {highlights.map((h, index) => (
                <li key={index} className="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50">
                  <svg className="h-5 w-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                  {h}
                </li>
              ))}
            </ul>
          </div>
        </section>
      )}

      {vis("cta") && (
        <section className="relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 py-20 text-white">
          <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute -right-40 -top-40 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl" />
            <div className="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-indigo-500/10 blur-3xl" />
          </div>
          <div className="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 className="mb-6 text-4xl font-bold">{t("site.home.cta_banner_title")}</h2>
            <p className="mx-auto mb-10 max-w-3xl text-xl text-blue-100">{t("site.home.cta_banner_intro")}</p>
            <div className="flex flex-col justify-center gap-4 sm:flex-row">
              <Link href="/admissions/apply" className="inline-flex items-center gap-2 rounded-xl bg-white px-10 py-4 text-lg font-semibold text-blue-800 shadow-lg transition-all hover:bg-gray-100 hover:shadow-xl">
                {t("site.home.cta_apply")}
                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
              </Link>
              <Link href="/contact" className="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-10 py-4 text-lg font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                {t("site.home.cta_contact")}
              </Link>
            </div>
          </div>
        </section>
      )}

      {vis("partners") && partnersContent.length > 0 && (
        <section className="border-t border-slate-100 bg-white py-12">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p className="mb-8 text-center text-sm font-semibold uppercase tracking-wider text-slate-400">{partnersH.title}</p>
            <div className="flex flex-wrap items-center justify-center gap-8 md:gap-14">
              {partnersContent.map((partner, index) => {
                const color = String(partner.color ?? "blue");
                const colorMap: Record<string, { bg: string; text: string }> = {
                  blue: { bg: "bg-blue-50", text: "text-blue-600" },
                  emerald: { bg: "bg-emerald-50", text: "text-emerald-600" },
                  amber: { bg: "bg-amber-50", text: "text-amber-600" },
                  purple: { bg: "bg-purple-50", text: "text-purple-600" },
                  rose: { bg: "bg-rose-50", text: "text-rose-600" },
                };
                const c = colorMap[color] ?? colorMap.blue;
                return (
                  <a key={index} href={String(partner.url ?? "#")} target="_blank" rel="noopener noreferrer" className="flex flex-col items-center gap-2 opacity-60 transition hover:opacity-100" title={String(partner.name ?? "")}>
                    <div className={`flex h-14 w-14 items-center justify-center rounded-full ${c.bg}`}>
                      <svg className={`h-7 w-7 ${c.text}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                    </div>
                    <span className="text-xs font-medium text-slate-500">{String(partner.name ?? "")}</span>
                  </a>
                );
              })}
            </div>
          </div>
        </section>
      )}
    </>
  );
}