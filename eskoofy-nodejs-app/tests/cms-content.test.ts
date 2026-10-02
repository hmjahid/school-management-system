import { describe, expect, it } from "vitest";
import { buildCmsContent, parseSegments } from "@/lib/cms-content";

function form(entries: Record<string, string>): FormData {
  const fd = new FormData();
  for (const [key, value] of Object.entries(entries)) fd.append(key, value);
  return fd;
}

describe("parseSegments", () => {
  it("splits bracket notation into path segments", () => {
    expect(parseSegments("content[sections][0][heading]")).toEqual(["content", "sections", "0", "heading"]);
    expect(parseSegments("content[hero][headline]")).toEqual(["content", "hero", "headline"]);
    expect(parseSegments("content")).toEqual(["content"]);
  });
});

describe("buildCmsContent", () => {
  it("folds grouped fields into a nested object", () => {
    const tree = buildCmsContent(form({ "content[hero][headline]": "Welcome", "content[hero][subtitle]": "Hi" }));
    expect(tree).toEqual({ hero: { headline: "Welcome", subtitle: "Hi" } });
  });

  it("builds arrays from numeric segments", () => {
    const tree = buildCmsContent(
      form({
        "content[sections][0][heading]": "A",
        "content[sections][0][body]": "one",
        "content[sections][1][heading]": "B",
      }),
    );
    expect(tree).toEqual({
      sections: [
        { heading: "A", body: "one" },
        { heading: "B" },
      ],
    });
  });

  it("returns the plain content field when no bracketed fields exist", () => {
    expect(buildCmsContent(form({ content: "Just text" }))).toBe("Just text");
  });

  it("returns an empty object when the form is empty", () => {
    expect(buildCmsContent(form({}))).toEqual({});
  });

  it("ignores metadata fields outside the content tree", () => {
    const tree = buildCmsContent(form({ title: "About", "content[intro]": "Hello" }));
    expect(tree).toEqual({ intro: "Hello" });
  });
});
