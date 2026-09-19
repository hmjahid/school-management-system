"use server";

import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { endSession, startSession, verifyPassword } from "@/lib/auth";

export async function loginAction(formData: FormData): Promise<void> {
  const email = String(formData.get("email") ?? "").trim();
  const password = String(formData.get("password") ?? "");
  const redirectTo = String(formData.get("redirect") ?? "/dashboard");

  const user = await prisma.users.findFirst({ where: { email, deleted_at: null } });

  if (!user) {
    redirect("/login?error=1");
  }

  const ok = await verifyPassword(password, user.password);
  if (!ok) {
    redirect("/login?error=1");
  }

  await startSession({
    id: Number(user.id),
    name: user.name,
    email: user.email,
    role: user.role,
  });

  redirect(redirectTo.startsWith("/") ? redirectTo : "/dashboard");
}

export async function logoutAction(): Promise<void> {
  await endSession();
  redirect("/login");
}
