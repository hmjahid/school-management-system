import { deflateRawSync, inflateRawSync } from "node:zlib";

/**
 * Minimal, dependency-free ZIP reader/writer used by the backup engine.
 *
 * The Laravel app's `backup:run` writes standard zips (deflate) via PHP's
 * ZipArchive; this module reads those (methods 0 + 8) and writes standard
 * zips (deflate) that PHP's ZipArchive can read back. Kept deliberately small
 * — no streaming, whole-buffer operations only, which is plenty for backups.
 *
 * Cross-variant portability rule: the layout inside the zip is the portable
 * backup contract (see docs/design/DATA-PORTABILITY.md).
 */

const LOCAL_HEADER = 0x04034b50;
const CENTRAL_HEADER = 0x02014b50;
const EOCD = 0x06054b50;

const UTF8_FLAG = 0x0800;

// ── CRC-32 ───────────────────────────────────────────────────────────────────

const CRC_TABLE = (() => {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) {
      c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    }
    table[n] = c >>> 0;
  }
  return table;
})();

export function crc32(data: Uint8Array): number {
  let crc = 0xffffffff;
  for (let i = 0; i < data.length; i++) {
    crc = CRC_TABLE[(crc ^ data[i]) & 0xff] ^ (crc >>> 8);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

// ── DOS date/time ────────────────────────────────────────────────────────────

function dosDateTime(date: Date): { time: number; date: number } {
  const d = date;
  const time = (d.getHours() << 11) | (d.getMinutes() << 5) | (d.getSeconds() >> 1);
  const y = Math.max(d.getFullYear() - 1980, 0);
  const dateWord = (y << 9) | ((d.getMonth() + 1) << 5) | d.getDate();
  return { time, date: dateWord };
}

// ── Writer ───────────────────────────────────────────────────────────────────

export interface ZipEntry {
  /** Entry path inside the archive (forward slashes, UTF-8). */
  name: string;
  data: Uint8Array | string;
  /** Compression method: 0 = stored, 8 = deflate. Defaults to deflate. */
  method?: 0 | 8;
  mtime?: Date;
}

export function createZip(entries: ZipEntry[]): Uint8Array {
  const chunks: Uint8Array[] = [];
  const central: Uint8Array[] = [];
  let offset = 0;

  for (const entry of entries) {
    const nameBytes = Buffer.from(entry.name, "utf8");
    const raw = typeof entry.data === "string" ? Buffer.from(entry.data, "utf8") : Buffer.from(entry.data);
    const method = entry.method ?? 8;
    const body = method === 0 ? raw : deflateRawSync(raw);
    const crc = crc32(raw);
    const { time, date } = dosDateTime(entry.mtime ?? new Date());

    const local = Buffer.alloc(30);
    local.writeUInt32LE(LOCAL_HEADER, 0);
    local.writeUInt16LE(20, 4); // version needed
    local.writeUInt16LE(UTF8_FLAG, 6);
    local.writeUInt16LE(method, 8);
    local.writeUInt16LE(time, 10);
    local.writeUInt16LE(date, 12);
    local.writeUInt32LE(crc, 14);
    local.writeUInt32LE(body.length, 18);
    local.writeUInt32LE(raw.length, 22);
    local.writeUInt16LE(nameBytes.length, 26);
    local.writeUInt16LE(0, 28); // extra length

    const localOffset = offset;
    chunks.push(local, nameBytes, body);
    offset += local.length + nameBytes.length + body.length;

    const cd = Buffer.alloc(46);
    cd.writeUInt32LE(CENTRAL_HEADER, 0);
    cd.writeUInt16LE(20, 4); // version made by
    cd.writeUInt16LE(20, 6); // version needed
    cd.writeUInt16LE(UTF8_FLAG, 8);
    cd.writeUInt16LE(method, 10);
    cd.writeUInt16LE(time, 12);
    cd.writeUInt16LE(date, 14);
    cd.writeUInt32LE(crc, 16);
    cd.writeUInt32LE(body.length, 20);
    cd.writeUInt32LE(raw.length, 24);
    cd.writeUInt16LE(nameBytes.length, 28);
    cd.writeUInt16LE(0, 30); // extra length
    cd.writeUInt16LE(0, 32); // comment length
    cd.writeUInt16LE(0, 34); // disk start
    cd.writeUInt16LE(0, 36); // internal attrs
    cd.writeUInt32LE(0, 38); // external attrs
    cd.writeUInt32LE(localOffset, 42);
    central.push(cd, nameBytes);
  }

  const centralBytes = Buffer.concat(central);
  const cdSize = Buffer.alloc(22);
  cdSize.writeUInt32LE(EOCD, 0);
  cdSize.writeUInt16LE(0, 4);
  cdSize.writeUInt16LE(0, 6);
  cdSize.writeUInt16LE(entries.length, 8);
  cdSize.writeUInt16LE(entries.length, 10);
  cdSize.writeUInt32LE(centralBytes.length, 12);
  cdSize.writeUInt32LE(offset, 16);
  cdSize.writeUInt16LE(0, 20);

  return Buffer.concat([...chunks, centralBytes, cdSize]);
}

// ── Reader ───────────────────────────────────────────────────────────────────

interface CentralEntry {
  name: string;
  method: number;
  crc: number;
  compSize: number;
  uncompSize: number;
  localOffset: number;
}

function parseEocd(buffer: Buffer): { cdOffset: number; cdSize: number; count: number } {
  let i = buffer.length - 22;
  const max = Math.max(0, buffer.length - 22 - 65536);
  while (i >= max) {
    if (buffer.readUInt32LE(i) === EOCD) {
      return {
        cdOffset: buffer.readUInt32LE(i + 16),
        cdSize: buffer.readUInt32LE(i + 12),
        count: buffer.readUInt16LE(i + 10),
      };
    }
    i--;
  }
  throw new Error("Not a valid zip archive (no end-of-central-directory record).");
}

function parseCentral(buffer: Buffer, cdOffset: number, cdSize: number, count: number): CentralEntry[] {
  const entries: CentralEntry[] = [];
  let pos = cdOffset;
  const end = cdOffset + cdSize;
  while (pos < end && entries.length < count) {
    if (buffer.readUInt32LE(pos) !== CENTRAL_HEADER) {
      throw new Error("Corrupt zip central directory.");
    }
    const nameLen = buffer.readUInt16LE(pos + 28);
    const extraLen = buffer.readUInt16LE(pos + 30);
    const commentLen = buffer.readUInt16LE(pos + 32);
    const name = buffer.subarray(pos + 46, pos + 46 + nameLen).toString("utf8");
    entries.push({
      name,
      method: buffer.readUInt16LE(pos + 10),
      crc: buffer.readUInt32LE(pos + 16),
      compSize: buffer.readUInt32LE(pos + 20),
      uncompSize: buffer.readUInt32LE(pos + 24),
      localOffset: buffer.readUInt32LE(pos + 42),
    });
    pos += 46 + nameLen + extraLen + commentLen;
  }
  return entries;
}

/** Read a zip buffer, returning entries keyed by their archive path. */
export function readZip(buffer: Uint8Array): Map<string, Uint8Array> {
  const buf = Buffer.isBuffer(buffer) ? buffer : Buffer.from(buffer);
  const { cdOffset, cdSize, count } = parseEocd(buf);
  const central = parseCentral(buf, cdOffset, cdSize, count);

  const out = new Map<string, Uint8Array>();
  for (const entry of central) {
    if (entry.name.endsWith("/")) continue; // directory entry
    const local = buf.subarray(entry.localOffset, entry.localOffset + 30);
    if (local.length < 30 || local.readUInt32LE(0) !== LOCAL_HEADER) {
      throw new Error(`Corrupt local header for ${entry.name}`);
    }
    const nameLen = local.readUInt16LE(26);
    const extraLen = local.readUInt16LE(28);
    const dataStart = entry.localOffset + 30 + nameLen + extraLen;
    const data = buf.subarray(dataStart, dataStart + entry.compSize);

    let raw: Buffer;
    if (entry.method === 0) {
      raw = Buffer.from(data);
    } else if (entry.method === 8) {
      raw = inflateRawSync(data);
    } else {
      throw new Error(`Unsupported zip method ${entry.method} for ${entry.name}`);
    }

    if (raw.length !== entry.uncompSize) {
      throw new Error(`Size mismatch for ${entry.name}`);
    }
    if (crc32(raw) !== entry.crc) {
      throw new Error(`CRC mismatch for ${entry.name}`);
    }
    out.set(entry.name, raw);
  }
  return out;
}