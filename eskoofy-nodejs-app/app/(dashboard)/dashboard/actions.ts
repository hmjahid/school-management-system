"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { MODEL_BY_NAME } from "@/lib/schema";
import { createRow, deleteRow, updateRow } from "@/lib/db-query";
import { currentUser } from "@/lib/auth";

/**
 * Generic create/update/delete actions behind every resource form — the Node
 * equivalent of the app's `store` / `update` / `destroy` controller methods.
 */

function payloadFrom(formData: FormData): Record<string, unknown> {
  const payload: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("__")) continue;
    payload[key] = typeof value === "string" ? value : "";
  }
  return payload;
}

export async function saveResource(formData: FormData): Promise<void> {
  const user = await currentUser();
  const base = String(formData.get("__base") ?? "/dashboard");
  if (!user) redirect(`/login?redirect=${encodeURIComponent(base)}`);

  const model = MODEL_BY_NAME.get(String(formData.get("__table") ?? ""));
  if (!model) redirect(base);

  const id = String(formData.get("__id") ?? "");
  const payload = payloadFrom(formData);

  if (id) await updateRow(model, id, payload);
  else await createRow(model, payload);

  revalidatePath(base);
  redirect(base);
}

export async function deleteResource(formData: FormData): Promise<void> {
  const user = await currentUser();
  const base = String(formData.get("__base") ?? "/dashboard");
  if (!user) redirect(`/login?redirect=${encodeURIComponent(base)}`);

  const model = MODEL_BY_NAME.get(String(formData.get("__table") ?? ""));
  const id = String(formData.get("__id") ?? "");
  if (model && id) await deleteRow(model, id);

  revalidatePath(base);
  redirect(base);
}
