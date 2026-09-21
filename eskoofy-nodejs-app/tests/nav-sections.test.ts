import { describe, expect, it } from "vitest";
import { NAV_GROUPS, NAV_SECTIONS, flattenNav, type NavGroup, type NavItem } from "@/lib/nav";
import en from "@/lang/en";

/**
 * The sidebar renders `NAV_SECTIONS` (the app's visual layout) while the parity
 * gate reads `NAV_GROUPS`. These tests keep the two from drifting: every item
 * and every group must appear in a section exactly once.
 */
function sectionItems(): NavItem[] {
  const out: NavItem[] = [];
  for (const section of NAV_SECTIONS) {
    for (const node of section.nodes) {
      if (node.type === "item") out.push(node.item);
      else out.push(...(node.group.items ?? []));
    }
  }
  return out;
}

function sectionGroups(): NavGroup[] {
  const out: NavGroup[] = [];
  for (const section of NAV_SECTIONS) {
    for (const node of section.nodes) {
      if (node.type === "group") out.push(node.group);
    }
  }
  return out;
}

describe("dashboard nav sections (visual layout)", () => {
  it("renders every NAV_GROUPS item exactly once", () => {
    const expected = flattenNav()
      .map((item) => `${item.key}:${item.path}`)
      .sort();
    const actual = sectionItems()
      .map((item) => `${item.key}:${item.path}`)
      .sort();
    expect(actual).toEqual(expected);
  });

  it("represents every NAV_GROUPS child group (collapsible groups or their flat items)", () => {
    const flatKeys = new Set(sectionItems().map((item) => `${item.key}:${item.path}`));
    const groupKeys = new Set(sectionGroups().map((group) => group.key));
    const missing: string[] = [];

    for (const group of NAV_GROUPS.flatMap((entry) => entry.children ?? [])) {
      if (group.children?.length) {
        if (!groupKeys.has(group.key)) missing.push(group.key);
      } else {
        for (const item of group.items ?? []) {
          if (!flatKeys.has(`${item.key}:${item.path}`)) missing.push(`${group.key}/${item.key}`);
        }
      }
    }
    expect(missing).toEqual([]);
  });

  it("keeps the app's section order", () => {
    expect(NAV_SECTIONS.map((section) => section.key)).toEqual([
      "main",
      "academic",
      "system",
      "website",
      "administration",
      "configuration",
      "help_group",
    ]);
  });

  it("gates the admin-only sections", () => {
    const gated = NAV_SECTIONS.filter((section) => section.adminOnly).map((section) => section.key);
    expect(gated).toEqual(["system", "website", "administration", "configuration"]);
  });

  it("has a translation for every rendered section and group label", () => {
    const keys = [
      ...NAV_SECTIONS.map((section) => `dashboard.${section.key}`),
      ...sectionGroups().map((group) => `dashboard.${group.key}`),
    ];
    expect(keys.filter((key) => !(key in en))).toEqual([]);
  });
});
