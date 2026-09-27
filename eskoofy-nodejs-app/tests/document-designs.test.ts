import { describe, expect, it } from "vitest";
import { sanitizeTheme, sanitizeWatermark, sanitizeCss, documentCss, isType, isTemplate } from "@/lib/document-designs";

describe("document-designs (Node port)", () => {
  it("recognises the five document types and four templates", () => {
    expect(["certificate", "testimonial", "marksheet", "admit_card", "id_card"].every(isType)).toBe(true);
    expect(isType("nonsense")).toBe(false);
    expect(["classic", "modern", "minimal", "bordered"].every(isTemplate)).toBe(true);
    expect(isTemplate("../../etc/passwd")).toBe(false);
  });

  it("clamps and sanitises a theme payload", () => {
    const theme = sanitizeTheme({
      primary_color: "not-a-color",
      base_font_size: 999,
      title_font_size: 0,
      border_style: "groovy",
      accent_bar: "weird",
      show_logo: "0",
    });

    expect(theme.primary_color).toBe("#1e40af");
    expect(theme.base_font_size).toBe(32);
    expect(theme.title_font_size).toBe(10);
    expect(theme.border_style).toBe("solid");
    expect(theme.accent_bar).toBe("none");
    expect(theme.show_logo).toBe(false);
  });

  it("produces dompdf-safe CSS (no var()/calc()/color-mix()/:not())", () => {
    const css = documentCss(
      sanitizeTheme({ primary_color: "#112233" }),
      sanitizeWatermark({ enabled: true, type: "text", text: "W" }),
      "",
    );

    expect(css).not.toContain("var(");
    expect(css).not.toContain("calc(");
    expect(css).not.toContain("color-mix(");
    expect(css).not.toContain(":not(");
  });

  it("emits watermark rules only when enabled", () => {
    const on = documentCss(sanitizeTheme({}), sanitizeWatermark({ enabled: true, type: "text", text: "WATER" }), "");
    expect(on).toContain(".doc-watermark");

    const off = documentCss(sanitizeTheme({}), sanitizeWatermark({ enabled: false }), "");
    expect(off).not.toContain(".doc-watermark");
  });

  it("strips executable bits from custom CSS", () => {
    const clean = sanitizeCss(
      ".x{color:red;}@import url(evil.css);<script>alert(1)</script>.y{background:url(https://evil/x.png)}",
    );

    expect(clean).not.toContain("@import");
    expect(clean).not.toContain("script");
    expect(clean).not.toContain("https:");
    expect(clean).toContain(".x{color:red;}");
  });
});