import { describe, expect, it } from "vitest";
import { createZip, crc32, readZip } from "@/lib/zip";

describe("zip", () => {
  it("round-trips stored entries", () => {
    const zip = createZip([
      { name: "a.txt", data: "hello", method: 0 },
      { name: "dir/b.txt", data: Buffer.from("world"), method: 0 },
    ]);
    const entries = readZip(zip);
    expect(entries.size).toBe(2);
    expect(entries.get("a.txt")!.toString()).toBe("hello");
    expect(entries.get("dir/b.txt")!.toString()).toBe("world");
  });

  it("round-trips deflate entries (the method PHP ZipArchive writes)", () => {
    const payload = "x".repeat(10_000) + "\nГармония\n";
    const zip = createZip([{ name: "f.json", data: payload, method: 8 }]);
    const entries = readZip(zip);
    expect(entries.get("f.json")!.toString()).toBe(payload);
  });

  it("round-trips a mix of UTF-8 names and binary data", () => {
    const binary = Buffer.from([0, 1, 2, 254, 255]);
    const zip = createZip([
      { name: "storage/app/public/ডকুমেন্ট.png", data: binary, method: 8 },
      { name: "MANIFEST.json", data: JSON.stringify({ ok: true }), method: 0 },
    ]);
    const entries = readZip(zip);
    expect(entries.get("storage/app/public/ডকুমেন্ট.png")?.length).toBe(binary.length);
    expect(JSON.parse(entries.get("MANIFEST.json")!.toString())).toEqual({ ok: true });
  });

  it("detects CRC corruption", () => {
    const zip = createZip([{ name: "a.txt", data: "hello", method: 0 }]);
    const buf = Buffer.from(zip);
    buf[36] ^= 0xff; // flip a byte inside the "hello" payload (offset 36 = 30 header + 5 name + 1)
    expect(() => readZip(buf)).toThrow(/CRC mismatch/i);
  });

  it("computes a known CRC-32", () => {
    expect(crc32(Buffer.from("ascii"))).toBe(0xd13adaf9);
  });
});