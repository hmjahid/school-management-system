"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { prisma } from "@/lib/prisma";
import { sanitizeCss, sanitizeTheme, sanitizeWatermark, isType, isTemplate } from "@/lib/document-designs";

function deny(base: string): never {
  redirect(`/login?redirect=${encodeURIComponent(base)}`);
}

export async function saveDesignAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_document_designs")) deny("/dashboard/document-designs");

  const type = String(formData.get("document_type") ?? "");
  if (!isType(type)) redirect("/dashboard/document-designs?error=type");

  const name = String(formData.get("name") ?? "").trim() || type.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
  const template = isTemplate(formData.get("template")) ? String(formData.get("template")) : "classic";

  const settings: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("settings[")) {
      const field = key.slice("settings[".length, -1);
      settings[field] = value;
    }
  }
  const wm: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("watermark[")) {
      const field = key.slice("watermark[".length, -1);
      wm[field] = value;
    }
  }

  const isDefault = formData.get("is_default") === "1";
  const isActive = formData.get("is_active") === "1";
  const id = Number(formData.get("id") ?? 0);

  if (isDefault) {
    await prisma.document_designs.updateMany({
      where: { document_type: type },
      data: { is_default: false },
    });
  }

  const data = {
    document_type: type,
    name,
    template,
    is_default: isDefault,
    is_active: isActive,
    settings: JSON.stringify(sanitizeTheme(settings)),
    watermark: JSON.stringify(sanitizeWatermark(wm, type)),
    custom_css: sanitizeCss(String(formData.get("custom_css") ?? "")),
  };

  if (id > 0) {
    await prisma.document_designs.update({ where: { id }, data });
  } else {
    await prisma.document_designs.create({ data });
  }

  revalidatePath("/dashboard/document-designs");
  redirect("/dashboard/document-designs?status=saved");
}

export async function deleteDesignAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_document_designs")) deny("/dashboard/document-designs");

  const id = Number(formData.get("id") ?? 0);
  if (id > 0) await prisma.document_designs.delete({ where: { id } });

  revalidatePath("/dashboard/document-designs");
  redirect("/dashboard/document-designs?status=deleted");
}