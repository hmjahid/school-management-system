"use client";

import { useState } from "react";
import Link from "next/link";
import { t } from "@/lib/i18n";
import { portalFormat, ATTENDANCE_BADGE, ATTENDANCE_DOT } from "@/lib/portal-data";
import { sendTeacherMessage } from "@/app/(site)/portal/actions";

type Row = Record<string, unknown>;

interface PortalTabProps {
  isStudent: boolean;
  isParent: boolean;
  student: Row | null;
  linkedChildren: { id: number; name: string; className: string | null; sectionName: string | null; roll: string | null }[];
  recentAttendance: Row[];
  examResults: Row[];
  feePayments: Row[];
  routine: [number, Row[]][];
  teachers: Row[];
  attendanceCalendar: [string, Row[]][];
  duesTimeline: Row[];
  sent: boolean;
  error: boolean;
}

function gradeBadge(grade: unknown): string {
  const g = String(grade ?? "");
  return g === "A+" || g === "A" ? "bg-green-100 text-green-800" : "bg-slate-100 text-slate-700";
}

function payBadge(status: unknown): string {
  const s = String(status ?? "");
  return s === "paid" ? "bg-green-100 text-green-800" : s === "pending" ? "bg-yellow-100 text-yellow-800" : "bg-red-100 text-red-800";
}

export default function PortalTabs({
  isStudent,
  isParent,
  student,
  linkedChildren,
  recentAttendance,
  examResults,
  feePayments,
  routine,
  teachers,
  attendanceCalendar,
  duesTimeline,
  sent,
  error,
}: PortalTabProps) {
  const [tab, setTab] = useState("profile");
  const { fmtDate, fmtTimeHM, DAY_NAMES } = portalFormat;

  const tabs: { key: string; label: string }[] = [
    { key: "profile", label: t("site.portal.section_profile") },
    { key: "attendance", label: "Attendance" },
    { key: "exams", label: "Exams" },
    { key: "fees", label: "Fees" },
    ...(isStudent ? [{ key: "routine", label: t("site.portal.routine") }] : []),
    ...(isParent ? [{ key: "dues", label: t("site.portal.dues_timeline") }] : []),
    ...(isParent ? [{ key: "calendar", label: t("site.portal.attendance_calendar") }] : []),
    { key: "message", label: t("site.portal.message_teacher") },
  ];

  const avgGpa = (() => {
    const values = examResults.map((r) => Number(r.grade_point ?? NaN)).filter((v) => Number.isFinite(v));
    if (values.length === 0) return null;
    return (values.reduce((a, b) => a + b, 0) / values.length).toFixed(2);
  })();

  return (
    <div className="mt-10 reveal">
      {sent ? (
        <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
          {t("site.portal.message_sent")}
        </p>
      ) : null}
      {error ? (
        <p className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
          Please check the form and try again.
        </p>
      ) : null}

      <div className="border-b border-slate-200">
        <nav className="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
          {tabs.map((item) => (
            <button
              key={item.key}
              type="button"
              onClick={() => setTab(item.key)}
              className={`whitespace-nowrap border-b-2 px-1 py-4 text-sm transition ${
                tab === item.key
                  ? "border-blue-600 font-semibold text-blue-600"
                  : "border-transparent font-medium text-slate-500 hover:text-slate-700"
              }`}
            >
              {item.label}
            </button>
          ))}
        </nav>
      </div>

      {tab === "profile" ? (
        <div className="mt-8 grid gap-6 lg:grid-cols-2">
          {isStudent && student ? (
            <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
              <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_profile")}</h2>
              <dl className="mt-4 space-y-3 text-sm">
                <div className="flex justify-between gap-4 border-b border-slate-50 pb-2">
                  <dt className="text-slate-500">{t("site.portal.label_class")}</dt>
                  <dd className="font-medium text-slate-900">{String((student.school_classes as Row | undefined)?.name ?? "—")}</dd>
                </div>
                <div className="flex justify-between gap-4 border-b border-slate-50 pb-2">
                  <dt className="text-slate-500">{t("site.portal.label_section")}</dt>
                  <dd className="font-medium text-slate-900">{String((student.sections as Row | undefined)?.name ?? "—")}</dd>
                </div>
                <div className="flex justify-between gap-4 border-b border-slate-50 pb-2">
                  <dt className="text-slate-500">{t("site.portal.label_roll")}</dt>
                  <dd className="font-medium text-slate-900">{String(student.roll_number ?? student.roll_no ?? "—")}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-slate-500">Email</dt>
                  <dd className="font-medium text-slate-900">{String((student.users as Row | undefined)?.email ?? "")}</dd>
                </div>
              </dl>
            </section>
          ) : null}

          {isParent && linkedChildren.length > 0 ? (
            <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
              <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_linked")}</h2>
              <ul className="mt-4 space-y-3">
                {linkedChildren.map((child) => (
                  <li key={child.id} className="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                      {child.name.slice(0, 1) || "S"}
                    </div>
                    <div>
                      <p className="font-medium text-slate-900">{child.name}</p>
                      <p className="text-xs text-slate-500">
                        {child.className ?? ""} {child.sectionName ?? ""}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            </section>
          ) : null}

          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">Recent Activity</h2>
            <div className="mt-4 space-y-3">
              <div className="flex items-center gap-3 text-sm">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <div>
                  <p className="text-slate-700">Logged in</p>
                  <p className="text-xs text-slate-400">{new Date().toLocaleString("en-US", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" })}</p>
                </div>
              </div>
              {examResults.length > 0 ? (
                <div className="flex items-center gap-3 text-sm">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-slate-700">Results updated</p>
                    <p className="text-xs text-slate-400">{fmtDate(examResults[0].updated_at)}</p>
                  </div>
                </div>
              ) : null}
            </div>
          </section>
        </div>
      ) : null}

      {tab === "attendance" ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_attendance")}</h2>
            {recentAttendance.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_attendance")}</p>
            ) : (
              <div className="mt-4 overflow-hidden rounded-xl border border-slate-100">
                <table className="min-w-full divide-y divide-slate-100 text-sm">
                  <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                      <th className="px-4 py-3">Date</th>
                      <th className="px-4 py-3">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-50 bg-white">
                    {recentAttendance.map((row, i) => (
                      <tr key={i} className="transition-colors hover:bg-slate-50">
                        <td className="px-4 py-2.5 text-slate-700">{fmtDate(row.date)}</td>
                        <td className="px-4 py-2.5">
                          <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${ATTENDANCE_BADGE(row.status)}`}>
                            <span className={`h-1.5 w-1.5 rounded-full ${ATTENDANCE_DOT(row.status)}`} />
                            {String(row.status ?? "").charAt(0).toUpperCase() + String(row.status ?? "").slice(1)}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </div>
      ) : null}

      {tab === "exams" ? (
        <div className="mt-8 grid gap-6 lg:grid-cols-2">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_exams")}</h2>
            {examResults.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_results")}</p>
            ) : (
              <div className="mt-4 space-y-2">
                {examResults.map((r, i) => {
                  const exam = r.exams as Row | undefined;
                  return (
                    <div key={i} className="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                      <div>
                        <p className="text-sm font-medium text-slate-900">{String(exam?.name ?? "Exam")}</p>
                        <p className="text-xs text-slate-500">{String(exam?.subject_name ?? "")}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-bold text-slate-900">
                          {String(r.obtained_marks ?? "—")}
                          <span className="text-xs font-normal text-slate-400">/{String(exam?.total_marks ?? "—")}</span>
                        </p>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${gradeBadge(r.grade)}`}>{String(r.grade ?? "—")}</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </section>

          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_progress")}</h2>
            {examResults.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_results")}</p>
            ) : (
              <>
                <div className="mt-4 grid gap-4 sm:grid-cols-3">
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">{t("site.portal.progress_counted")}</div>
                    <div className="mt-2 text-2xl font-bold text-slate-900">{examResults.length}</div>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">{t("site.portal.progress_avg_gpa")}</div>
                    <div className="mt-2 text-2xl font-bold text-slate-900">{avgGpa ?? "—"}</div>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">{t("site.portal.progress_latest_grade")}</div>
                    <div className="mt-2 text-2xl font-bold text-slate-900">{String(examResults[0].grade ?? "—")}</div>
                  </div>
                </div>
                <p className="mt-3 text-xs text-slate-500">{t("site.portal.progress_note")}</p>
                <Link href="/portal/progress" className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                  {t("site.portal.progress_link")} →
                </Link>
              </>
            )}
          </section>
        </div>
      ) : null}

      {tab === "fees" ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_fees")}</h2>
            {feePayments.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_fee_payments")}</p>
            ) : (
              <>
                <div className="mt-4 space-y-2">
                  {feePayments.slice(0, 8).map((fp, i) => (
                    <div key={i} className="flex items-center justify-between gap-2 rounded-xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                      <div>
                        <p className="font-mono text-xs text-slate-500">{String(fp.invoice_number ?? "")}</p>
                        <p className="text-xs text-slate-400">{fmtDate(fp.created_at)}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-bold text-slate-900">৳ {Number(fp.paid_amount ?? 0).toFixed(2)}</p>
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${payBadge(fp.status)}`}>
                          {String(fp.status ?? "").charAt(0).toUpperCase() + String(fp.status ?? "").slice(1)}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
                <Link href="/payments" className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                  {t("site.portal.full_payment_portal")} →
                </Link>
              </>
            )}
          </section>
        </div>
      ) : null}

      {tab === "routine" && isStudent ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.class_routine")}</h2>
            {routine.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_routine")}</p>
            ) : (
              <div className="mt-4 space-y-6">
                {routine.map(([day, items]) => (
                  <div key={day}>
                    <h3 className="mb-2 text-sm font-semibold text-slate-700">{DAY_NAMES[Number(day)] ?? `Day ${day}`}</h3>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                      {items.map((item, i) => {
                        const subject = item.subjects as Row | undefined;
                        const teacher = item.teachers as Row | undefined;
                        return (
                          <div key={i} className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <p className="font-medium text-slate-900">{String(subject?.name ?? "Subject")}</p>
                            <p className="text-xs text-slate-500">
                              {fmtTimeHM(item.start_time)} – {fmtTimeHM(item.end_time)}
                            </p>
                            <p className="text-xs text-slate-500">{String((teacher?.users as Row | undefined)?.name ?? "")}</p>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </section>
        </div>
      ) : null}

      {tab === "dues" && isParent ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.dues_timeline")}</h2>
            {duesTimeline.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_fee_payments")}</p>
            ) : (
              <div className="relative mt-6 border-l-2 border-slate-200 pl-6">
                {duesTimeline.map((fp, i) => (
                  <div key={i} className="relative mb-6 last:mb-0">
                    <span className={`absolute -left-1.5 h-3 w-3 rounded-full ${String(fp.status ?? "") === "paid" ? "bg-emerald-500" : "bg-amber-500"}`} />
                    <p className="text-xs text-slate-500">{fmtDate(fp.payment_date ?? fp.created_at)}</p>
                    <p className="font-medium text-slate-900">
                      ৳ {Number(fp.balance ?? fp.amount ?? 0).toFixed(2)} — {String(fp.status ?? "").charAt(0).toUpperCase() + String(fp.status ?? "").slice(1)}
                    </p>
                    <p className="text-xs text-slate-500">{String(fp.invoice_number ?? "")}</p>
                  </div>
                ))}
              </div>
            )}
          </section>
        </div>
      ) : null}

      {tab === "calendar" && isParent ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.attendance_calendar")}</h2>
            {attendanceCalendar.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_attendance")}</p>
            ) : (
              <div className="mt-4 grid grid-cols-7 gap-2 text-center text-xs">
                {["S", "M", "T", "W", "T", "F", "S"].map((d, i) => (
                  <div key={i} className="py-1 font-semibold text-slate-400">
                    {d}
                  </div>
                ))}
                {attendanceCalendar.map(([date, rows]) => {
                  const present = rows.filter((r) => ["present", "late", "half_day"].includes(String(r.status ?? ""))).length;
                  const absent = rows.length - present;
                  return (
                    <div key={date} className={`rounded-lg border border-slate-100 p-2 ${absent > 0 ? "bg-red-50" : present > 0 ? "bg-emerald-50" : "bg-slate-50"}`}>
                      <span className="font-medium">{new Date(date).getDate()}</span>
                      <span className={`block text-[0.6rem] ${absent > 0 ? "text-red-600" : "text-emerald-600"}`}>
                        {present}/{rows.length}
                      </span>
                    </div>
                  );
                })}
              </div>
            )}
          </section>
        </div>
      ) : null}

      {tab === "message" ? (
        <div className="mt-8">
          <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.message_teacher")}</h2>
            {teachers.length === 0 ? (
              <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_teachers_assigned")}</p>
            ) : (
              <form action={sendTeacherMessage} className="mt-4 max-w-xl space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700">{t("site.portal.select_teacher")}</label>
                  <select name="teacher_id" required defaultValue="" className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="" disabled>
                      —
                    </option>
                    {teachers.map((teacher) => (
                      <option key={String(teacher.id)} value={String(teacher.id)}>
                        {String((teacher.users as Row | undefined)?.name ?? t("site.portal.fallback_student"))}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700">{t("site.portal.subject")}</label>
                  <input type="text" name="subject" required maxLength={120} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700">{t("site.portal.message")}</label>
                  <textarea name="body" required maxLength={2000} rows={4} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <button type="submit" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                  {t("site.portal.send_message")}
                </button>
              </form>
            )}
          </section>
        </div>
      ) : null}
    </div>
  );
}