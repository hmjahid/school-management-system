import { resolveModel } from "@/lib/resources";
import type { ModelMeta } from "@/lib/schema";

/**
 * Classify a dashboard path into a CRUD screen, mirroring the app's
 * `index / create / store / show / edit / update / destroy` resource routes.
 *
 *   /dashboard/students            -> index   (base /dashboard/students)
 *   /dashboard/students/create     -> create  (base /dashboard/students)
 *   /dashboard/students/12         -> show    (id 12)
 *   /dashboard/students/12/edit    -> edit    (id 12)
 */
export type ResourceMode = "index" | "create" | "show" | "edit";

export interface ResolvedResource {
  mode: ResourceMode;
  model?: ModelMeta;
  id?: string;
  basePath: string;
}

export function resolveResource(segments: string[]): ResolvedResource {
  const clean = segments.filter(Boolean);
  const model = resolveModel(clean);
  const last = clean[clean.length - 1];
  const numericIndex = clean.findIndex((segment) => /^\d+$/.test(segment));

  let mode: ResourceMode = "index";
  let id: string | undefined;
  let base = clean;

  if (last === "create") {
    mode = "create";
    base = clean.slice(0, -1);
  } else if (last === "edit" && numericIndex >= 0) {
    mode = "edit";
    id = clean[numericIndex];
    base = clean.slice(0, numericIndex);
  } else if (numericIndex >= 0) {
    mode = "show";
    id = clean[numericIndex];
    base = clean.slice(0, numericIndex);
  }

  return { mode, model, id, basePath: `/dashboard/${base.join("/")}` };
}
