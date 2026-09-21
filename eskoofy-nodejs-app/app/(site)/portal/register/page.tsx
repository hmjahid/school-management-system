import { redirect } from "next/navigation";

export const dynamic = "force-dynamic";

/** Mirrors the `portal.register` closure: send applicants to the online application. */
export default function PortalRegisterPage() {
  redirect("/admissions/apply?register=1");
}