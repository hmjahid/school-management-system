import { prisma } from "@/lib/prisma";
import { locale, t } from "@/lib/i18n";
import { PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

function initialsOf(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .slice(0, 2)
    .join("");
}

export default async function CommitteePage() {
  const n = locale();
  const members = await prisma.committee_members.findMany({
    where: { is_active: true },
    orderBy: { sort_order: "asc" },
  });

  const rows = members.map((member) => {
    const name = n === "bn" && member.name_bn ? member.name_bn : member.name;
    const designation = n === "bn" && member.designation_bn ? member.designation_bn : member.designation;
    const bio = n === "bn" && member.bio_bn ? member.bio_bn : member.bio;
    return { id: String(member.id), name, designation, bio, photo: member.photo, phone: member.phone, email: member.email };
  });

  return (
    <div className="bg-white">
      <PageHero title={t("site.home.committee_title")} subtitle={t("site.home.committee_intro")} />

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {rows.length === 0 ? (
          <div className="py-16 text-center text-gray-500">
            <svg className="mx-auto h-16 w-16 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
              <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
            </svg>
            <p className="mt-4 text-lg font-medium text-gray-700">No committee members added yet.</p>
            <p className="mt-1 text-sm text-gray-500">Committee members will appear here once added in the dashboard.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {rows.map((member) => (
              <div
                key={member.id}
                className="group rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
              >
                <div className="mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-3xl font-bold text-blue-600 shadow-lg ring-4 ring-white transition-transform duration-300 group-hover:scale-105">
                  {member.photo ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={member.photo} alt={member.name} className="h-full w-full object-cover" />
                  ) : (
                    initialsOf(member.name) || "M"
                  )}
                </div>
                <h3 className="mt-4 text-xl font-semibold text-gray-900">{member.name}</h3>
                <p className="mt-1 text-sm font-medium text-blue-600">{member.designation}</p>
                {member.bio ? <p className="mt-3 text-sm leading-relaxed text-gray-500">{member.bio.slice(0, 150)}</p> : null}
                <div className="mt-4 flex items-center justify-center gap-3">
                  {member.phone ? (
                    <a href={`tel:${member.phone}`} className="text-gray-400 transition-colors hover:text-blue-600" title={member.phone}>
                      <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                      </svg>
                    </a>
                  ) : null}
                  {member.email ? (
                    <a href={`mailto:${member.email}`} className="text-gray-400 transition-colors hover:text-blue-600" title={member.email}>
                      <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                      </svg>
                    </a>
                  ) : null}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}