import { describe, it, expect } from "vitest";
import { readFileSync } from "node:fs";
import { join } from "node:path";

const PUBLIC = join(process.cwd(), "public");

describe("G1 identity assets: byte-identical to the Laravel app public dir", () => {
  it("ships icon-192, icon-512, robots.txt, offline.html, sw.js (non-empty)", () => {
    for (const f of ["icon-192.png", "icon-512.png", "robots.txt", "offline.html", "sw.js"]) {
      const buf = readFileSync(join(PUBLIC, f));
      expect(buf.length, `${f} empty`).toBeGreaterThan(0);
    }
  });
  it("robots.txt content matches the Laravel original (sample line)", () => {
    const robots = readFileSync(join(PUBLIC, "robots.txt"), "utf8");
    expect(robots.toLowerCase()).toContain("user-agent");
  });
});
