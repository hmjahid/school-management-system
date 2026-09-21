"use client";

import { useId, useState } from "react";
import { t } from "@/lib/i18n";

export interface FacultyMember {
  id: string;
  name: string;
  designation: string;
  qualification: string | null;
  phone: string | null;
  joiningDate: string | null;
}

const VISIBLE_COUNT = 6;

function initialsOf(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .slice(0, 2)
    .join("");
}

function formatDate(value: string | null): string | null {
  if (!value) return null;
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return null;
  return d.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
}

function AccordionIcon() {
  return (
    <svg className="h-4 w-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
    </svg>
  );
}

export default function FacultyClient({
  teachers,
  emptyText,
}: {
  teachers: FacultyMember[];
  emptyText: string;
}) {
  const generatedId = useId();
  const [query, setQuery] = useState("");
  const [department, setDepartment] = useState("all");
  const [expanded, setExpanded] = useState(false);

  const q = query.trim().toLowerCase();
  const dept = department.toLowerCase() || "general";

  const visible = teachers.filter((member) => {
    const haystack = `${member.name} ${member.designation} ${member.qualification ?? ""}`.toLowerCase();
    return (!q || haystack.includes(q)) && (dept === "all" || dept === "general");
  });

  const shown = expanded ? visible : visible.slice(0, VISIBLE_COUNT);
  const hasMore = visible.length > VISIBLE_COUNT;

  if (teachers.length === 0) {
    return (
      <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
        <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <p className="mt-4 text-sm text-slate-500">{emptyText}</p>
      </div>
    );
  }

  return (
    <>
      <div className="mb-10 flex flex-col gap-4 sm:flex-row">
        <div className="relative flex-1">
          <svg className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="search"
            value={query}
            onChange={(event) => {
              setQuery(event.target.value);
              setExpanded(false);
            }}
            placeholder="Search teachers by name, designation, or qualification..."
            className="w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          />
        </div>
        <select
          value={department}
          onChange={(event) => {
            setDepartment(event.target.value);
            setExpanded(false);
          }}
          aria-label="Department"
          className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
        >
          <option value="all">All Departments</option>
          <option value="science">Science</option>
          <option value="arts">Arts</option>
          <option value="commerce">Commerce</option>
          <option value="sports">Sports</option>
        </select>
      </div>

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {shown.map((member) => {
          const joiningDate = formatDate(member.joiningDate);
          return (
            <div key={member.id} className="group rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
              <div className="flex flex-col items-center text-center">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 shadow-lg ring-4 ring-white transition-transform duration-300 group-hover:scale-105">
                  {initialsOf(member.name) || "S"}
                </div>
                <h3 className="mt-4 text-lg font-semibold text-slate-900">{member.name}</h3>
                <p className="text-sm text-slate-500">{member.designation || "Teacher"}</p>
                {member.qualification ? <p className="mt-1 text-xs text-slate-400">{member.qualification}</p> : null}
              </div>

              <details className="group mt-4 border-t border-slate-100 pt-4">
                <summary className="flex cursor-pointer items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-500 transition-colors hover:text-blue-600">
                  <span>View Details</span>
                  <AccordionIcon />
                </summary>
                <div className="mt-3 space-y-2 text-sm text-slate-600">
                  {member.phone ? (
                    <p className="flex items-center gap-2">
                      <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                      </svg>
                      {member.phone}
                    </p>
                  ) : null}
                  {joiningDate ? (
                    <p className="flex items-center gap-2">
                      <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      Joined {joiningDate}
                    </p>
                  ) : null}
                </div>
              </details>
            </div>
          );
        })}
        {visible.length === 0 ? (
          <div className="sm:col-span-2 lg:col-span-3 xl:col-span-4">
            <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
              <p className="text-sm text-slate-500">No faculty members match your search.</p>
            </div>
          </div>
        ) : null}
      </div>

      {hasMore ? (
        <div className="mt-10 text-center">
          <p className="mb-4 text-sm text-slate-500">
            {t("site.faculty_page.showing_of", { shown: String(expanded ? visible.length : VISIBLE_COUNT), total: String(visible.length) })}
          </p>
          <button
            type="button"
            onClick={() => setExpanded((value) => !value)}
            id={`${generatedId}-toggle`}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 hover:shadow-md"
          >
            <span>{expanded ? t("site.faculty_page.see_less") : t("site.faculty_page.see_more")}</span>
            <svg className={`h-4 w-4 transition-transform duration-200 ${expanded ? "rotate-180" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>
      ) : null}
    </>
  );
}