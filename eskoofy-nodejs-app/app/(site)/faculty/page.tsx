import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import FacultyClient, { type FacultyMember } from "@/components/site/FacultyClient";

export const dynamic = "force-dynamic";

export default async function FacultyPage() {
  const teachers = await prisma.teachers.findMany({
    where: { status: "active", deleted_at: null },
    include: { users: true },
    orderBy: { joining_date: "desc" },
    take: 80,
  });

  const members: FacultyMember[] = teachers.map((teacher) => {
    const user = teacher.users;
    return {
      id: String(teacher.id),
      name: user?.name ?? t("site.faculty_page.staff_fallback"),
      designation: t("site.home.teacher_fallback"),
      qualification: teacher.qualification,
      phone: teacher.phone,
      joiningDate: teacher.joining_date ? teacher.joining_date.toISOString() : null,
    };
  });

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.home.teachers_title")}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{t("site.faculty_page.directory_heading")}</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <FacultyClient teachers={members} emptyText={t("site.faculty_page.empty")} />
      </div>
    </div>
  );
}