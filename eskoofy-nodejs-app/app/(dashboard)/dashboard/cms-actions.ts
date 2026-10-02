"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { buildCmsContent } from "@/lib/cms-content";

/**
 * CMS page save — mirrors `CmsWebController::update()`. The Node editor posts
 * bracket-notation fields (`content[sections][0][heading]`) which
 * `buildCmsContent()` folds back into the JSON content tree the public site
 * reads.
 */
export async function saveCmsPage(page: string, formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect(`/login?redirect=${encodeURIComponent(`/dashboard/cms/${page}/edit`)}`);
  if (!can(user.role, "manage_cms")) redirect(`/dashboard/cms/${page}/edit?error=1`);

  const title = String(formData.get("title") ?? "").trim();
  const metaDescription = String(formData.get("meta_description") ?? "").trim();
  const metaKeywords = String(formData.get("meta_keywords") ?? "").trim();
  const content = buildCmsContent(formData);
  const stored = typeof content === "string" ? content : JSON.stringify(content);

  const existing = await prisma.website_contents.findUnique({ where: { page } });
  const data = {
    title: title || existing?.title || page,
    title_en: title || existing?.title_en || page,
    meta_description: metaDescription || null,
    meta_description_en: metaDescription || null,
    meta_keywords: metaKeywords || null,
    content: stored,
    content_en: stored,
    cms_input_mode: "form",
    updated_at: new Date(),
  };

  if (existing) {
    await prisma.website_contents.update({ where: { id: existing.id }, data });
  } else {
    await prisma.website_contents.create({ data: { page, is_active: true, ...data } });
  }

  revalidatePath(`/dashboard/cms/${page}/edit`);
  revalidatePath(`/${page === "home" ? "" : page}`);
  redirect(`/dashboard/cms/${page}/edit?saved=1`);
}
