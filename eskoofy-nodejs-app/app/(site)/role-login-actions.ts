"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { startSession, verifyPassword } from "@/lib/auth";

/**
 * Role-aware login used by the student/guardian login pages.
 *
 * Next.js requires server actions to be top-level "use server" functions, so
 * the role is read from the hidden form field instead of a factory function.
 */
export async function roleLoginAction(formData: FormData): Promise<void> {
  const role = String(formData.get("role") ?? "");
  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  const password = String(formData.get("password") ?? "");
  const redirectTo = String(formData.get("redirect") ?? "/dashboard");
  const roleSlug = role === "guardian" ? "guardian" : "student";

  const user = await prisma.users.findUnique({ where: { email } });
  if (!user || user.role !== role || user.deleted_at) redirect(`/${roleSlug}-login?error=1`);

  const ok = await verifyPassword(password, user.password);
  if (!ok) redirect(`/${roleSlug}-login?error=1`);

  await startSession({
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
  });
  redirect(redirectTo.startsWith("/") ? redirectTo : "/dashboard");
}
