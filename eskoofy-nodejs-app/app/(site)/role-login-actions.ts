import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { startSession, verifyPassword } from "@/lib/auth";

export function roleLoginAction(role: string) {
  return async function action(formData: FormData): Promise<void> {
    const email = String(formData.get("email") ?? "").trim().toLowerCase();
    const password = String(formData.get("password") ?? "");
    const redirectTo = String(formData.get("redirect") ?? "/dashboard");

    const user = await prisma.users.findUnique({ where: { email } });
    if (!user) redirect(`/${role}-login?error=1`);
    if (!user || user.role !== role) redirect(`/${role}-login?error=1`);
    if (user && user.deleted_at) redirect(`/${role}-login?error=1`);

    const ok = user ? await verifyPassword(password, user.password) : false;
    if (!ok) redirect(`/${role}-login?error=1`);

    await startSession({
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role,
    });
    redirect(redirectTo.startsWith("/") ? redirectTo : "/dashboard");
  };
}
