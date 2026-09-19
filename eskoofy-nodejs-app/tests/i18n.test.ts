import { describe, expect, it } from "vitest";
import en from "@/lang/en";
import bn from "@/lang/bn";
import { availableLocales, t } from "@/lib/i18n";

describe("i18n", () => {
  it("keeps en and bn key sets identical", () => {
    const missingInBn = Object.keys(en).filter((key) => !(key in bn));
    const missingInEn = Object.keys(bn).filter((key) => !(key in en));
    expect(missingInBn).toEqual([]);
    expect(missingInEn).toEqual([]);
  });

  it("resolves a key per locale", () => {
    expect(t("brand.name", {}, "en")).toBe("Eskoofy");
    expect(t("brand.name", {}, "bn")).toBe("এস্কুফি");
  });

  it("substitutes placeholders and falls back to the key", () => {
    expect(t("missing.key", {}, "en")).toBe("missing.key");
  });

  it("exposes locales present in dictionaries", () => {
    expect(availableLocales()).toContain("en");
  });

  it("ports the app's dashboard + site keys", () => {
    for (const key of ["dashboard.students", "dashboard.finance", "site.nav.home", "site.footer.quick_links_title"]) {
      expect(Object.keys(en)).toContain(key);
    }
    expect(Object.keys(en).length).toBeGreaterThan(700);
  });
});
