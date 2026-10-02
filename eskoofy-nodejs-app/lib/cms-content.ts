/**
 * CMS editor form → JSON content tree.
 *
 * The editor renders one input per content leaf using bracket notation
 * (`content[sections][0][heading]`); this folds those fields back into the tree
 * the public site reads. Port of the content-building half of
 * `CmsWebController::update()` (the Node editor is schema-free, so keys are
 * reconstructed structurally instead of from a page registry).
 */

/** Split `content[sections][0][heading]` into `["content","sections","0","heading"]`. */
export function parseSegments(name: string): string[] {
  const segments: string[] = [];
  const re = /([^[\]]+)|\[([^\]]*)\]/g;
  let match: RegExpExecArray | null;
  while ((match = re.exec(name)) !== null) segments.push(match[1] ?? match[2] ?? "");
  return segments;
}

function assign(root: Record<string, unknown>, path: string[], value: unknown): void {
  let cursor: Record<string, unknown> | unknown[] = root;

  for (let i = 0; i < path.length - 1; i++) {
    const key = path[i]!;
    const wantArray = /^\d+$/.test(path[i + 1]!);

    if (Array.isArray(cursor)) {
      const index = Number(key);
      if (cursor[index] === undefined) cursor[index] = wantArray ? [] : {};
      cursor = cursor[index] as Record<string, unknown> | unknown[];
    } else {
      if (cursor[key] === undefined) cursor[key] = wantArray ? [] : {};
      cursor = cursor[key] as Record<string, unknown> | unknown[];
    }
  }

  const last = path[path.length - 1]!;
  if (Array.isArray(cursor)) cursor[Number(last)] = value;
  else cursor[last] = value;
}

/** Fold `content[...]` form fields into a tree, or return the plain text field. */
export function buildCmsContent(form: FormData): Record<string, unknown> | string {
  const root: Record<string, unknown> = {};
  let hasStructuredFields = false;
  let sawPlainField = false;
  let plain = "";

  for (const [name, value] of form.entries()) {
    if (value instanceof File) continue;
    if (name === "content") {
      plain = String(value);
      sawPlainField = true;
      continue;
    }
    if (!name.startsWith("content[")) continue;

    hasStructuredFields = true;
    assign(root, parseSegments(name).slice(1), String(value));
  }

  if (hasStructuredFields) return root;
  return sawPlainField ? plain : {};
}
