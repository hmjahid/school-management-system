/**
 * @fileoverview Tests for the site-ui label resolver and the settings-content
 * persistence helpers. These guard the parity of the Node Global Labels / CMS /
 * About tabs against `DashboardSettingController` and `WebsiteContent`.
 *
 * The DB is never touched: every function under test here is pure.
 */

import { describe, expect, it } from "vitest";
import {
  deepMerge,
  flattenSiteLabels,
  getPath,
  isPlainObject,
  isSimpleList,
  listPaths,
  normalizeListValues,
  pruneIdentical,
  setPath,
  siteUiDefaults,
} from "@/lib/site-ui";
import { SITE_UI_DEFAULTS } from "@/lib/site-labels";
import {
  aboutIntroFromTree,
  aboutRowsFromTree,
  mergeAboutTree,
  mergeLabelOverrides,
  normalizeAboutForm,
  normalizeCmsPayload,
} from "@/lib/settings-content";
import { resolvePageContent } from "@/lib/site-data";

describe("deepMerge", () => {
  it("merges nested objects instead of replacing them", () => {
    expect(deepMerge({ a: { b: 1, c: 2 } }, { a: { c: 9 } })).toEqual({ a: { b: 1, c: 9 } });
  });

  it("replaces arrays wholesale, like array_replace_recursive", () => {
    expect(deepMerge({ list: [1, 2, 3] }, { list: [9] })).toEqual({ list: [9] });
  });

  it("does not mutate its arguments", () => {
    const base = { a: { b: 1 } };
    deepMerge(base, { a: { b: 2 } });
    expect(base).toEqual({ a: { b: 1 } });
  });
});

describe("getPath / setPath", () => {
  it("reads and writes dotted paths", () => {
    const tree: Record<string, unknown> = {};
    setPath(tree, "nav.home", "Home");
    setPath(tree, "footer.links.ministry", ["a", "b"]);
    expect(getPath(tree, "nav.home")).toBe("Home");
    expect(getPath(tree, "footer.links.ministry")).toEqual(["a", "b"]);
  });

  it("returns undefined for missing paths without throwing", () => {
    expect(getPath({}, "nav.home")).toBeUndefined();
    expect(getPath({ nav: 1 }, "nav.home")).toBeUndefined();
  });
});

describe("isSimpleList", () => {
  it("treats an empty array as a simple list (PHP: $val === [])", () => {
    expect(isSimpleList([])).toBe(true);
  });

  it("accepts sequential string arrays", () => {
    expect(isSimpleList(["a", "b"])).toBe(true);
  });

  it("accepts nested EMPTY arrays, matching the PHP guard", () => {
    expect(isSimpleList([[], []])).toBe(true);
  });

  it("rejects non-empty nested arrays", () => {
    expect(isSimpleList([["x"]])).toBe(false);
  });

  it("rejects arrays of objects", () => {
    expect(isSimpleList([{ heading: "x" }])).toBe(false);
  });

  it("rejects associative arrays", () => {
    expect(isSimpleList({ heading: "x" })).toBe(false);
  });

  it("rejects non-arrays", () => {
    expect(isSimpleList("a")).toBe(false);
    expect(isSimpleList(null)).toBe(false);
    expect(isSimpleList(7)).toBe(false);
  });
});

describe("flattenSiteLabels", () => {
  const defaults = {
    nav: { home: "Home", about: "About Us" },
    footer: { about: "Footer copy" },
    links: ["one", "two"],
    structured: [{ heading: "Kept as a section" }],
  };
  const overrides = { nav: { home: "Homepage" } };

  it("emits one row per string leaf with its dotted path", () => {
    const rows = flattenSiteLabels(defaults, overrides);
    const home = rows.find((r) => r.path === "nav.home");

    expect(home).toBeDefined();
    expect(home!.section).toBe("nav");
    expect(home!.label).toBe("home");
    expect(home!.enDefault).toBe("Home");
    expect(home!.enOverride).toBe("Homepage");
    expect(home!.isList).toBe(false);
  });

  it("leaves enOverride empty when there is no override", () => {
    const row = flattenSiteLabels(defaults, overrides).find((r) => r.path === "footer.about");
    expect(row!.enDefault).toBe("Footer copy");
    expect(row!.enOverride).toBe("");
  });

  it("marks simple lists and carries their defaults", () => {
    const row = flattenSiteLabels(defaults, overrides).find((r) => r.path === "links");
    expect(row!.isList).toBe(true);
    expect(row!.listDefaults).toEqual(["one", "two"]);
  });

  it("does not descend into structured (object-bearing) arrays", () => {
    const paths = flattenSiteLabels(defaults, overrides).map((r) => r.path);
    expect(paths).not.toContain("structured.0.heading");
  });

  it("flattens a deeply nested path into section + label", () => {
    const row = flattenSiteLabels({ site: { footer: { contact: { email_label: "Email" } } } }, {}).find(
      (r) => r.path === "site.footer.contact.email_label",
    );
    expect(row!.section).toBe("site");
    expect(row!.label).toBe("footer.contact.email_label");
  });
});

describe("listPaths / normalizeListValues", () => {
  const defaults = { links: ["a"], nav: { home: "Home" }, grid: [{ x: 1 }] };
  const lists = listPaths(defaults);

  it("collects only simple-list paths", () => {
    expect(lists).toEqual(["links"]);
  });

  it("turns a newline textarea back into a trimmed array", () => {
    expect(normalizeListValues({ links: " a \n\n b \n " }, lists)).toEqual({ links: ["a", "b"] });
  });

  it("leaves non-list keys untouched", () => {
    expect(normalizeListValues({ nav: { home: "Start" } }, lists)).toEqual({ nav: { home: "Start" } });
  });

  it("normalises nested lists using their full path", () => {
    const nested = { footer: { links: "x\ny" } };
    expect(normalizeListValues(nested, ["footer.links"])).toEqual({ footer: { links: ["x", "y"] } });
  });
});

describe("pruneIdentical", () => {
  it("drops BN leaves identical to EN so the language file wins", () => {
    expect(pruneIdentical({ nav: { home: "Home" } }, { nav: { home: "Home" } })).toEqual({});
  });

  it("keeps genuinely translated BN leaves", () => {
    expect(pruneIdentical({ nav: { home: "হোম" } }, { nav: { home: "Home" } })).toEqual({
      nav: { home: "হোম" },
    });
  });

  it("drops empty BN leaves", () => {
    expect(pruneIdentical({ nav: { home: "   " } }, { nav: { home: "Home" } })).toEqual({});
  });

  it("prunes recursively", () => {
    expect(
      pruneIdentical({ a: { b: { same: "x", diff: "y" } } }, { a: { b: { same: "x", diff: "z" } } }),
    ).toEqual({ a: { b: { diff: "y" } } });
  });
});

describe("mergeLabelOverrides", () => {
  it("writes an override over an empty tree", () => {
    expect(mergeLabelOverrides({}, { nav: { home: "Homepage" } }, "en")).toEqual({
      nav: { home: "Homepage" },
    });
  });

  it("merges into existing overrides without dropping siblings", () => {
    const existing = { nav: { home: "Homepage", about: "Us" } };
    expect(mergeLabelOverrides(existing, { nav: { home: "Start" } }, "en")).toEqual({
      nav: { home: "Start", about: "Us" },
    });
  });

  it("treats an empty string as 'revert to default' and removes the key", () => {
    const existing = { nav: { home: "Homepage" } };
    expect(mergeLabelOverrides(existing, { nav: { home: "" } }, "en")).toEqual({ nav: {} });
  });

  it("converts list textareas into arrays", () => {
    const out = mergeLabelOverrides({}, { footer: { ministry_links: "a\nb" } }, "en");
    expect(out).toEqual({ footer: { ministry_links: ["a", "b"] } });
  });
});

describe("SITE_UI_DEFAULTS parity", () => {
  it("exposes the same top-level sections in both locales", () => {
    expect(Object.keys(SITE_UI_DEFAULTS.en).sort()).toEqual(Object.keys(SITE_UI_DEFAULTS.bn).sort());
  });

  it("matches the reference app's section count", () => {
    // lang/en/site_frontend.php has 29 top-level keys.
    expect(Object.keys(SITE_UI_DEFAULTS.en)).toHaveLength(29);
  });

  it("has the sections the site reads", () => {
    for (const key of ["nav", "footer", "home", "pages", "page_sections"]) {
      expect(SITE_UI_DEFAULTS.en).toHaveProperty(key);
    }
  });

  it("siteUiDefaults falls back to English for an unknown locale", () => {
    expect(siteUiDefaults("fr")).toBe(SITE_UI_DEFAULTS.en);
    expect(siteUiDefaults("bn")).toBe(SITE_UI_DEFAULTS.bn);
  });
});

describe("normalizeCmsPayload", () => {
  it("nulls out blank text fields so they clear the column", () => {
    expect(normalizeCmsPayload({ school_name: "  ", tagline: "Hi" })).toEqual({
      school_name: null,
      tagline: "Hi",
    });
  });

  it("nulls out blank social URLs", () => {
    const out = normalizeCmsPayload({ facebook_url: "", website: "https://x.test" });
    expect(out.facebook_url).toBeNull();
    expect(out.website).toBe("https://x.test");
  });

  it("coerces checkbox values to booleans", () => {
    expect(normalizeCmsPayload({ show_facebook: "1", show_twitter: "0" })).toEqual({
      show_facebook: true,
      show_twitter: false,
    });
  });

  it("ignores fields that were not posted", () => {
    expect(normalizeCmsPayload({ school_name: "A" })).toEqual({ school_name: "A" });
  });
});

describe("normalizeAboutForm", () => {
  const base = { intro_en: " Intro ", intro_bn: " ভূমিকা " };

  it("trims the intro for both locales", () => {
    const trees = normalizeAboutForm({ ...base, rows: [] });
    expect(trees.en.intro).toBe("Intro");
    expect(trees.bn.intro).toBe("ভূমিকা");
  });

  it("splits paragraphs on blank lines", () => {
    const trees = normalizeAboutForm({
      ...base,
      rows: [{ heading_en: "H", paragraphs_en: "one\n\ntwo", heading_bn: "", paragraphs_bn: "" }],
    });
    expect(trees.en.sections).toEqual([{ heading: "H", paragraphs: ["one", "two"] }]);
  });

  it("builds the EN and BN section lists independently", () => {
    const trees = normalizeAboutForm({
      ...base,
      rows: [
        { heading_en: "English only", paragraphs_en: "body", heading_bn: "", paragraphs_bn: "" },
        { heading_en: "", paragraphs_en: "", heading_bn: "বাংলা", paragraphs_bn: "শরীর" },
      ],
    });
    expect(trees.en.sections).toHaveLength(1);
    expect(trees.bn.sections).toHaveLength(1);
    expect(trees.bn.sections[0]!.heading).toBe("বাংলা");
  });

  it("drops a section that is empty in every locale", () => {
    const trees = normalizeAboutForm({
      ...base,
      rows: [{ heading_en: "  ", paragraphs_en: "", heading_bn: "", paragraphs_bn: "" }],
    });
    expect(trees.en.sections).toEqual([]);
    expect(trees.bn.sections).toEqual([]);
  });

  it("keeps a section with a heading but no paragraphs", () => {
    const trees = normalizeAboutForm({
      ...base,
      rows: [{ heading_en: "Only heading", paragraphs_en: "", heading_bn: "", paragraphs_bn: "" }],
    });
    expect(trees.en.sections).toEqual([{ heading: "Only heading", paragraphs: [] }]);
  });
});

describe("about rows round-trip", () => {
  it("renders stored sections back into editor rows", () => {
    const tree = { intro: "Hi", sections: [{ heading: "A", paragraphs: ["p1", "p2"] }] };
    const rows = aboutRowsFromTree(tree, "en");

    expect(aboutIntroFromTree(tree)).toBe("Hi");
    expect(rows).toEqual([
      { heading_en: "A", paragraphs_en: "p1\n\np2", heading_bn: "", paragraphs_bn: "" },
    ]);
  });

  it("round-trips through normalizeAboutForm without loss", () => {
    const tree = { intro: "Hi", sections: [{ heading: "A", paragraphs: ["p1", "p2"] }] };
    const rows = aboutRowsFromTree(tree, "en");
    const trees = normalizeAboutForm({ intro_en: "Hi", intro_bn: "", rows });

    expect(trees.en).toEqual(tree);
  });

  it("ignores non-object sections in the stored tree", () => {
    expect(aboutRowsFromTree({ sections: ["oops", { heading: "ok" }] }, "en")).toHaveLength(1);
  });
});

describe("mergeAboutTree", () => {
  it("preserves keys the About form does not edit", () => {
    const existing = { intro: "old", sections: [], gallery_id: 7 };
    const merged = mergeAboutTree(existing, { intro: "new", sections: [{ heading: "S", paragraphs: [] }] });

    expect(merged.gallery_id).toBe(7);
    expect(merged.intro).toBe("new");
  });
});

describe("isPlainObject", () => {
  it("rejects arrays and null", () => {
    expect(isPlainObject({})).toBe(true);
    expect(isPlainObject([])).toBe(false);
    expect(isPlainObject(null)).toBe(false);
    expect(isPlainObject("s")).toBe(false);
  });
});

describe("resolvePageContent (WebsiteContent::cloneForPublic parity)", () => {
  const aboutRow = {
    title: "About Us",
    title_en: "About Us",
    meta_description: "About our school",
    content_en: JSON.stringify({
      intro: "Welcome to our school.",
      hero_design: "classic",
      sections: [{ heading: "History", paragraphs: ["Founded in 1990."] }],
    }),
    content_bn: JSON.stringify({
      intro: "আমাদের বিদ্যালয়ে স্বাগতম।",
      sections: [{ heading: "ইতিহাস", paragraphs: ["১৯৯০ সালে প্রতিষ্ঠিত।"] }],
    }),
  };

  it("resolves the English tree from content_en for en", () => {
    const { content, title } = resolvePageContent(aboutRow, "en", "about");
    expect(title).toBe("About Us");
    expect(content.intro).toBe("Welcome to our school.");
    expect(content.hero_design).toBe("classic");
  });

  it("merges real Bengali leaves and keeps untranslated config keys", () => {
    const { content } = resolvePageContent(aboutRow, "bn", "about");
    expect(content.intro).toBe("আমাদের বিদ্যালয়ে স্বাগতম।");
    expect(content.sections).toEqual([{ heading: "ইতিহাস", paragraphs: ["১৯৯০ সালে প্রতিষ্ঠিত।"] }]);
    // `hero_design` is a selection key with no translation — it must survive.
    expect(content.hero_design).toBe("classic");
  });

  it("drops Bengali leaves identical to English so the language file wins", () => {
    const row = {
      content_en: JSON.stringify({ intro: "English", sections: [{ heading: "H" }] }),
      content_bn: JSON.stringify({ intro: "English", sections: [{ heading: "H" }] }),
    };
    expect(resolvePageContent(row, "bn", "about").content).toEqual({});
  });

  it("falls back to the legacy content column when content_en is empty", () => {
    const row = { content: JSON.stringify({ intro: "Legacy intro" }), content_en: null };
    expect(resolvePageContent(row, "en", "about").content).toEqual({ intro: "Legacy intro" });
  });

  it("humanizes the page slug when no title is stored", () => {
    expect(resolvePageContent({}, "en", "our-history").title).toBe("Our History");
  });

  it("falls back to title_bn / meta_description_bn for bn", () => {
    const row = { title_en: "About", title_bn: "আমাদের সম্পর্কে", meta_description_en: "EN", meta_description_bn: "বাংলা" };
    const resolved = resolvePageContent(row, "bn", "about");
    expect(resolved.title).toBe("আমাদের সম্পর্কে");
    expect(resolved.meta_description).toBe("বাংলা");
  });
});