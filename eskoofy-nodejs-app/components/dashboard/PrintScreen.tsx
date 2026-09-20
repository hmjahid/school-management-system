import { prisma } from "@/lib/prisma";
import { getSiteSettings } from "@/lib/site-settings";

/**
 * Bespoke print/PDF screens — mirror the app's standalone blade layouts:
 *   - admit-cards/print.blade.php
 *   - certificates/print.blade.php
 *   - student-id-cards/print.blade.php
 * These render a print-ready document (window.print()), not the admin shell.
 */

function formatDate(value: unknown): string {
  if (!value) return "N/A";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

export async function PrintScreen({ table, id }: { table: string; id: number }) {
  const settings = await getSiteSettings();

  if (table === "admit_cards") {
    const card = await prisma.admit_cards.findFirst({
      where: { id },
      include: { students: true, exams: true },
    });
    if (!card) return <p>Admit card not found.</p>;
    const student = (card.students ?? {}) as Record<string, unknown>;
    const exam = (card.exams ?? {}) as Record<string, unknown>;
    let details: Record<string, unknown> = {};
    try { details = card.details ? JSON.parse(String(card.details)) : {}; } catch { /* ignore */ }
    const headerText = String(details.header_text ?? settings.schoolName);
    const footerText = String(details.footer_text ?? settings.address);

    return (
      <div className="p-5">
        <div className="mx-auto max-w-[600px] rounded-2xl border-[3px] border-blue-800 p-8">
          <div className="border-b-2 border-dashed border-slate-300 pb-4 text-center">
            <h1 className="text-2xl font-bold text-blue-800">{headerText}</h1>
            <h2 className="mt-1 text-lg text-slate-700">Admit Card</h2>
            <div className="text-xs text-slate-500">{footerText}</div>
          </div>
          <dl className="mt-4 grid grid-cols-[1fr_2fr] gap-2 text-sm">
            <dt className="font-bold text-slate-600">Student Name:</dt><dd>{String(student.name ?? "N/A")}</dd>
            <dt className="font-bold text-slate-600">Roll Number:</dt><dd>{String(student.roll_number ?? student.roll_no ?? "N/A")}</dd>
            <dt className="font-bold text-slate-600">Exam:</dt><dd>{String(exam.name ?? "N/A")}</dd>
            <dt className="font-bold text-slate-600">Card Number:</dt><dd className="font-mono">{String(card.admit_card_number ?? "N/A")}</dd>
            <dt className="font-bold text-slate-600">Issue Date:</dt><dd>{formatDate(card.issue_date)}</dd>
          </dl>
          <div className="mt-5 border-t-2 border-dashed border-slate-300 pt-4 text-center text-xs text-slate-500">
            <p>This admit card is valid for the exam mentioned above.</p>
            <p className="mt-2">Authorized signature: ___________________</p>
          </div>
        </div>
        <div className="mt-6 text-center">
          <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Print / Save PDF
          </button>
        </div>
      </div>
    );
  }

  if (table === "certificates") {
    const cert = await prisma.certificates.findFirst({ where: { id } });
    if (!cert) return <p>Certificate not found.</p>;
    const c = cert as unknown as Record<string, unknown>;
    return (
      <div className="p-5">
        <div className="mx-auto max-w-[600px] rounded-2xl border-[3px] border-blue-800 p-8 text-center">
          <h1 className="text-2xl font-bold text-blue-800">{settings.schoolName}</h1>
          <h2 className="mt-2 text-xl text-slate-700">{String(c.type ?? "Certificate")}</h2>
          <p className="mt-4 text-sm text-slate-600">{String(c.content ?? c.description ?? "")}</p>
          <p className="mt-6 text-xs text-slate-500">Authorized signature: ___________________</p>
        </div>
        <div className="mt-6 text-center">
          <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Print / Save PDF
          </button>
        </div>
      </div>
    );
  }

  if (table === "student_id_cards") {
    const card = await prisma.student_id_cards.findFirst({ where: { id } });
    if (!card) return <p>ID card not found.</p>;
    const c = card as unknown as Record<string, unknown>;
    return (
      <div className="p-5">
        <div className="mx-auto max-w-[380px] rounded-2xl border-2 border-blue-700 bg-white p-6 text-center shadow-lg">
          <div className="mx-auto h-16 w-16 rounded-full bg-blue-100" />
          <h1 className="mt-3 text-lg font-bold text-blue-800">{settings.schoolName}</h1>
          <div className="mt-3 space-y-1 text-sm">
            <p><b>ID:</b> {String(c.card_number ?? c.id_number ?? c.id ?? "N/A")}</p>
            <p><b>Name:</b> {String(c.student_name ?? c.name ?? "N/A")}</p>
          </div>
        </div>
        <div className="mt-6 text-center">
          <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Print / Save PDF
          </button>
        </div>
      </div>
    );
  }

  return <p>Print screen not implemented for this resource.</p>;
}
