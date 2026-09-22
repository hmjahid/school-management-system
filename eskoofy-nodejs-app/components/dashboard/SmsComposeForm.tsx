"use client";

import { useMemo, useState } from "react";
import { previewSmsCampaign } from "@/app/(dashboard)/dashboard/sms-actions";

const AUDIENCE_OPTIONS: Array<{ value: string; label: string }> = [
  { value: "all_users", label: "All users (students + staff)" },
  { value: "students_class", label: "Students in a class" },
  { value: "students_section", label: "Students in a section" },
  { value: "students_shift", label: "Students in a shift" },
  { value: "students_individual", label: "Specific students" },
  { value: "staff_role", label: "Staff with a role" },
  { value: "staff_individual", label: "Specific staff" },
];

interface Option {
  id: number;
  name: string;
}

interface StudentOption {
  id: number;
  user_id: number;
  name: string;
  phone: string;
}

interface StaffOption {
  id: number;
  name: string;
  phone: string;
  role: string;
}

const inputClass = "admin-input w-full rounded-lg border border-slate-300 px-3 py-2 text-sm";
const labelClass = "mb-1 block text-sm font-medium text-slate-700";
const fieldClass = "rounded-xl border border-slate-200 bg-white p-5 shadow-sm";

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className={labelClass}>{label}</label>
      {children}
    </div>
  );
}

/**
 * Client-side compose form — mirrors `dashboard/sms/compose.blade.php`.
 * Submits to `previewSmsCampaign`, which persists a draft campaign and
 * redirects to the preview step.
 */
export function SmsComposeForm({
  classes,
  sections,
  shifts,
  staff,
  students,
}: {
  classes: Array<Option & { shift: string | null }>;
  sections: Option[];
  shifts: string[];
  staff: StaffOption[];
  students: StudentOption[];
}) {
  const [audience, setAudience] = useState("all_users");
  const [studentQuery, setStudentQuery] = useState("");
  const [staffQuery, setStaffQuery] = useState("");
  const [message, setMessage] = useState(
    "Dear parent, this is an automated message from the school. Please ensure your child attends classes regularly. Thank you."
  );

  const filteredStudents = useMemo(() => {
    if (!studentQuery.trim()) return students;
    const q = studentQuery.trim().toLowerCase();
    return students.filter((s) => s.name.toLowerCase().includes(q) || s.phone.toLowerCase().includes(q));
  }, [students, studentQuery]);

  const filteredStaff = useMemo(() => {
    if (!staffQuery.trim()) return staff;
    const q = staffQuery.trim().toLowerCase();
    return staff.filter((s) => s.name.toLowerCase().includes(q) || s.phone.toLowerCase().includes(q));
  }, [staff, staffQuery]);

  const smsCount = Math.max(1, Math.ceil(message.length / 160));

  const listBox = "mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200";

  return (
    <form action={previewSmsCampaign} className="grid gap-6 lg:grid-cols-3">
      <div className="space-y-6 lg:col-span-2">
        <div className={fieldClass}>
          <Field label="Campaign name">
            <input name="name" required placeholder="e.g. October fee reminder" className={inputClass} />
          </Field>
        </div>

        <div className={fieldClass}>
          <span className={labelClass}>Audience</span>
          <div className="mt-2 grid gap-2 sm:grid-cols-2">
            {AUDIENCE_OPTIONS.map((opt) => (
              <label
                key={opt.value}
                className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  name="audience_type"
                  value={opt.value}
                  checked={audience === opt.value}
                  onChange={() => setAudience(opt.value)}
                  className="h-4 w-4 rounded border-slate-300 text-brand-600"
                />
                {opt.label}
              </label>
            ))}
          </div>
        </div>

        {audience === "students_class" && (
          <div className={fieldClass}>
            <Field label="Class">
              <select name="school_class_id" className={inputClass}>
                {classes.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </Field>
          </div>
        )}

        {audience === "students_section" && (
          <div className={fieldClass}>
            <Field label="Section">
              <select name="section_id" className={inputClass}>
                {sections.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </select>
            </Field>
          </div>
        )}

        {audience === "students_shift" && (
          <div className={fieldClass}>
            <Field label="Shift">
              <select name="shift" className={inputClass}>
                {shifts.map((s) => (
                  <option key={s} value={s}>
                    {s[0]?.toUpperCase() + s.slice(1)}
                  </option>
                ))}
              </select>
            </Field>
          </div>
        )}

        {audience === "students_individual" && (
          <div className={fieldClass}>
            <Field label="Students (with phone numbers)">
              <input
                type="search"
                placeholder="Filter by name or phone…"
                value={studentQuery}
                onChange={(e) => setStudentQuery(e.target.value)}
                className={inputClass}
              />
              <div className={listBox}>
                {filteredStudents.map((s) => (
                  <label key={s.id} className="flex cursor-pointer items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-sm last:border-0 hover:bg-slate-50">
                    <span className="flex min-w-0 items-center gap-2">
                      <input type="checkbox" name="user_ids" value={s.user_id} className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                      <span className="truncate">{s.name}</span>
                    </span>
                    <span className="shrink-0 font-mono text-xs text-slate-500">{s.phone || "no phone"}</span>
                  </label>
                ))}
                {filteredStudents.length === 0 && <p className="px-3 py-6 text-center text-sm text-slate-400">No students match.</p>}
              </div>
            </Field>
          </div>
        )}

        {audience === "staff_role" && (
          <div className={fieldClass}>
            <Field label="Role">
              <select name="role_name" className={inputClass}>
                <option value="teacher">Teacher</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </Field>
          </div>
        )}

        {audience === "staff_individual" && (
          <div className={fieldClass}>
            <Field label="Staff (with phone numbers)">
              <input
                type="search"
                placeholder="Filter by name or phone…"
                value={staffQuery}
                onChange={(e) => setStaffQuery(e.target.value)}
                className={inputClass}
              />
              <div className={listBox}>
                {filteredStaff.map((s) => (
                  <label key={s.id} className="flex cursor-pointer items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-sm last:border-0 hover:bg-slate-50">
                    <span className="flex min-w-0 items-center gap-2">
                      <input type="checkbox" name="user_ids" value={s.id} className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                      <span className="truncate">{s.name}</span>
                    </span>
                    <span className="shrink-0 text-xs capitalize text-slate-500">{s.role} · {s.phone || "no phone"}</span>
                  </label>
                ))}
                {filteredStaff.length === 0 && <p className="px-3 py-6 text-center text-sm text-slate-400">No staff match.</p>}
              </div>
            </Field>
          </div>
        )}

        <div className={fieldClass}>
          <Field label="Message">
            <textarea
              name="message"
              rows={5}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className={`${inputClass} resize-y`}
            />
            <div className="mt-2 flex items-center justify-between text-xs text-slate-500">
              <span>{message.length} characters</span>
              <span className="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{smsCount} SMS × 160 chars</span>
            </div>
          </Field>
        </div>
      </div>

      <div className="space-y-6">
        <div className={fieldClass}>
          <Field label="Schedule (optional)">
            <input type="datetime-local" name="scheduled_at" className={inputClass} />
            <p className="mt-1 text-xs text-slate-500">Leave empty to send as soon as it&apos;s confirmed on the next step.</p>
          </Field>
        </div>

        <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
          <h3 className="text-sm font-semibold text-slate-900">Ready to preview?</h3>
          <p className="mt-1 text-sm text-slate-600">
            You&apos;ll review the recipients and the final message before the campaign is sent.
          </p>
          <button type="submit" className="mt-4 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
            Preview campaign
          </button>
        </div>
      </div>
    </form>
  );
}