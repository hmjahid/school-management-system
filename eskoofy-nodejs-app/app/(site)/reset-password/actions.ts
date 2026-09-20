import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

export async function resetRequestAction(formData: FormData): Promise<void> {
  const email = String(formData.get("email") ?? "").trim().toLowerCase();
  if (!email) redirect("/forgot-password?error=1");
  const user = await prisma.users.findUnique({ where: { email } });
  if (!user) redirect("/forgot-password?error=1");
  redirect("/forgot-password?sent=1");
}
