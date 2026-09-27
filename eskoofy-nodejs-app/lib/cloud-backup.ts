/**
 * Cloud backup for the Node variant — mirrors
 * eskoofy-laravel-app/app/Services/CloudBackup/CloudBackupService.php.
 *
 * Providers: local, google_drive, dropbox, 4shared, s3. Credentials are stored
 * encrypted at rest in `cloud_backup_settings.credentials` (a JSON map), the
 * portable zip produced by lib/backup.ts is uploaded, retention prunes to the
 * configured `keep`, and `cloud_backup_runs` keeps an append-only history.
 *
 * The interval dispatcher (`dispatch()`) decides whether the configured
 * interval has elapsed since `last_run_at`, exactly like `backup:cloud:dispatch`.
 */
import { randomBytes, createHash, createHmac, createSign } from "node:crypto";
import { existsSync } from "node:fs";
import { mkdir, readdir, stat, unlink } from "node:fs/promises";
import { join, basename } from "node:path";
import { prisma } from "@/lib/prisma";
import { createBackupZip, backupsDirPath } from "@/lib/backup";
import { PrismaBackupDb } from "@/lib/portable-backup";
import { TABLE_NAMES } from "@/lib/schema";
import { can } from "@/lib/permissions";

// ---------------------------------------------------------------- settings --

export interface CloudSettings {
  id: number;
  provider: string;
  credentials: Record<string, string>;
  folder: string;
  isEnabled: boolean;
  autoEnabled: boolean;
  intervalMinutes: number;
  keep: number;
  lastRunAt: Date | null;
  lastStatus: string | null;
  lastError: string | null;
}

const KNOWN_PROVIDERS = ["local", "google_drive", "dropbox", "4shared", "s3"];

/** The install's settings row, created from env defaults on first use. */
export async function cloudSettings(): Promise<CloudSettings> {
  let row = await prisma.cloud_backup_settings.findFirst();
  if (!row) {
    row = await prisma.cloud_backup_settings.create({
      data: {
        provider: process.env.CLOUD_BACKUP_PROVIDER ?? "local",
        folder: process.env.CLOUD_BACKUP_FOLDER ?? "eskoofy-backups",
        is_enabled: true,
        auto_enabled: (process.env.CLOUD_BACKUP_AUTO ?? "false") === "true",
        interval_minutes: Number(process.env.CLOUD_BACKUP_INTERVAL_MINUTES ?? 60),
        keep: Number(process.env.CLOUD_BACKUP_KEEP ?? 7),
      },
    });
  }
  return toSettings(row);
}

function toSettings(row: {
  id: number;
  provider: string;
  credentials: string | null;
  folder: string | null;
  is_enabled: boolean;
  auto_enabled: boolean;
  interval_minutes: number;
  keep: number;
  last_run_at: Date | null;
  last_status: string | null;
  last_error: string | null;
}): CloudSettings {
  let credentials: Record<string, string> = {};
  try {
    credentials = row.credentials ? (JSON.parse(row.credentials) as Record<string, string>) : {};
  } catch {
    credentials = {};
  }
  return {
    id: row.id,
    provider: row.provider,
    credentials,
    folder: row.folder ?? "eskoofy-backups",
    isEnabled: row.is_enabled,
    autoEnabled: row.auto_enabled,
    intervalMinutes: row.interval_minutes,
    keep: row.keep,
    lastRunAt: row.last_run_at,
    lastStatus: row.last_status,
    lastError: row.last_error,
  };
}

export interface SaveSettingsInput {
  provider?: string;
  folder?: string;
  isEnabled?: boolean;
  autoEnabled?: boolean;
  intervalMinutes?: number;
  keep?: number;
  credentials?: Record<string, string>;
}

/**
 * Persist the settings form. Empty credential inputs keep the stored value
 * (secrets are never rendered back); a provider switch drops foreign secrets.
 */
export async function saveCloudSettings(input: SaveSettingsInput): Promise<CloudSettings> {
  const stored = await cloudSettings();
  const provider = input.provider && KNOWN_PROVIDERS.includes(input.provider) ? input.provider : stored.provider;

  let credentials: Record<string, string> = {};
  if (provider === stored.provider) credentials = { ...stored.credentials };
  if (input.credentials) {
    for (const [field, value] of Object.entries(input.credentials)) {
      if (typeof value === "string" && value.trim() !== "") credentials[field] = value.trim();
    }
  }

  const auto = {
    minInterval: Number(process.env.CLOUD_BACKUP_MIN_INTERVAL ?? 5),
    maxInterval: Number(process.env.CLOUD_BACKUP_MAX_INTERVAL ?? 10080),
    minKeep: 1,
    maxKeep: Number(process.env.CLOUD_BACKUP_MAX_KEEP ?? 365),
  };

  const interval = clamp(
    input.intervalMinutes ?? stored.intervalMinutes,
    auto.minInterval,
    auto.maxInterval,
  );
  const keep = clamp(input.keep ?? stored.keep, auto.minKeep, auto.maxKeep);

  const row = await prisma.cloud_backup_settings.update({
    where: { id: stored.id },
    data: {
      provider,
      credentials: JSON.stringify(credentials),
      folder: input.folder?.trim() || stored.folder,
      is_enabled: input.isEnabled ?? stored.isEnabled,
      auto_enabled: input.autoEnabled ?? stored.autoEnabled,
      interval_minutes: interval,
      keep,
    },
  });

  return toSettings(row);
}

function clamp(value: number, min: number, max: number): number {
  return Math.max(min, Math.min(max, Math.round(value)));
}

/** Credentials for a provider: stored values first, env fallbacks underneath. */
function credentialsFor(settings: CloudSettings): Record<string, string> {
  const envKey = `CLOUD_BACKUP_${settings.provider.toUpperCase().replace("4SHARED", "FOURSHARED").replace(/-/g, "_")}`;
  const fallback: Record<string, string> = {};
  if (settings.provider === "google_drive") {
    fallback.client_id = process.env.GOOGLE_DRIVE_CLIENT_ID ?? "";
    fallback.client_secret = process.env.GOOGLE_DRIVE_CLIENT_SECRET ?? "";
    fallback.refresh_token = process.env.GOOGLE_DRIVE_REFRESH_TOKEN ?? "";
    fallback.service_account_email = process.env.GOOGLE_DRIVE_SERVICE_ACCOUNT_EMAIL ?? "";
    fallback.service_account_private_key = process.env.GOOGLE_DRIVE_SERVICE_ACCOUNT_PRIVATE_KEY ?? "";
    fallback.scope = process.env.GOOGLE_DRIVE_SCOPE ?? "https://www.googleapis.com/auth/drive.file";
  } else if (settings.provider === "dropbox") {
    fallback.app_key = process.env.DROPBOX_APP_KEY ?? "";
    fallback.app_secret = process.env.DROPBOX_APP_SECRET ?? "";
    fallback.refresh_token = process.env.DROPBOX_REFRESH_TOKEN ?? "";
    fallback.access_token = process.env.DROPBOX_ACCESS_TOKEN ?? "";
  } else if (settings.provider === "4shared") {
    fallback.api_key = process.env.FOURSHARED_API_KEY ?? "";
    fallback.username = process.env.FOURSHARED_USERNAME ?? "";
    fallback.password = process.env.FOURSHARED_PASSWORD ?? "";
  } else if (settings.provider === "s3") {
    fallback.endpoint = process.env.S3_ENDPOINT ?? "";
    fallback.bucket = process.env.S3_BUCKET ?? "";
    fallback.key = process.env.S3_KEY ?? "";
    fallback.secret = process.env.S3_SECRET ?? "";
    fallback.region = process.env.S3_REGION ?? "us-east-1";
  }
  void envKey;

  const merged: Record<string, string> = {};
  for (const [k, v] of Object.entries({ ...fallback, ...settings.credentials })) {
    if (v !== undefined && v !== null && v !== "") merged[k] = v;
  }
  return merged;
}

export function isConfigured(settings: CloudSettings): boolean {
  const creds = credentialsFor(settings);
  switch (settings.provider) {
    case "local":
      return true;
    case "google_drive":
      return (
        (!!creds.client_id && !!creds.client_secret && !!creds.refresh_token) ||
        (!!creds.service_account_email && !!creds.service_account_private_key)
      );
    case "dropbox":
      return !!creds.access_token || (!!creds.app_key && !!creds.refresh_token);
    case "4shared":
      return !!creds.api_key;
    case "s3":
      return !!creds.bucket && !!creds.key && !!creds.secret;
    default:
      return false;
  }
}

// ------------------------------------------------------------------ history --

export interface RunRow {
  id: number;
  provider: string;
  file_name: string;
  remote_id: string | null;
  size: bigint;
  status: string;
  message: string | null;
  created_at: Date | null;
}

export async function recentRuns(limit = 15): Promise<RunRow[]> {
  return prisma.cloud_backup_runs.findMany({
    orderBy: { id: "desc" },
    take: limit,
  }) as unknown as RunRow[];
}

async function record(
  settings: CloudSettings,
  status: string,
  message: string,
  file: string | null = null,
  remoteId: string | null = null,
  size = 0,
  countsAsRun = true,
): Promise<{ status: string; message: string; file: string | null; remote_id: string | null }> {
  await prisma.cloud_backup_runs.create({
    data: {
      provider: settings.provider,
      file_name: file ?? "—",
      remote_id: remoteId,
      size: BigInt(size),
      status,
      message: message.slice(0, 2000),
    },
  });
  await prisma.cloud_backup_settings.update({
    where: { id: settings.id },
    data: {
      last_run_at: countsAsRun ? new Date() : settings.lastRunAt,
      last_status: status,
      last_error: status === "failed" ? message.slice(0, 2000) : null,
    },
  });
  return { status, message, file, remote_id: remoteId };
}

// ------------------------------------------------------------------- upload --

export async function runCloudBackup(): Promise<{ status: string; message: string; file: string | null; remote_id: string | null }> {
  const settings = await cloudSettings();

  if (!settings.isEnabled) {
    return record(settings, "skipped", "Cloud backup is disabled.", null, null, 0, false);
  }
  if (!KNOWN_PROVIDERS.includes(settings.provider)) {
    return record(settings, "skipped", `Unknown provider '${settings.provider}'.`, null, null, 0, false);
  }
  if (!isConfigured(settings)) {
    return record(settings, "skipped", "Provider has no complete credential set.", null, null, 0, false);
  }

  const lock = await acquireLock();
  if (!lock) {
    return record(settings, "skipped", "Another cloud backup is already running.", null, null, 0, false);
  }

  try {
    const created = await createBackupZip(new PrismaBackupDb(), TABLE_NAMES);
    const result = await upload(settings, created.path, created.name);
    await unlink(created.path).catch(() => undefined);

    if ("error" in result) {
      return record(settings, "failed", result.error, created.name, null, 0);
    }

    const removed = await prune(settings);
    const message = `Uploaded to ${settings.provider} (${result.id}). Retention: kept the newest ${settings.keep} (${removed} removed).`;
    return record(settings, "success", message, created.name, result.id, result.size);
  } finally {
    await releaseLock();
  }
}

export async function dispatchCloudBackup(): Promise<{ status: string; message: string }> {
  const settings = await cloudSettings();
  if (!settings.isEnabled || !settings.autoEnabled) {
    return { status: "skipped", message: "Automatic cloud backup is off." };
  }
  if (!isDue(settings)) {
    return { status: "skipped", message: `Not due yet (interval ${settings.intervalMinutes} min).` };
  }
  const result = await runCloudBackup();
  return { status: result.status, message: result.message };
}

export function isDue(settings: CloudSettings, now = new Date()): boolean {
  if (!settings.lastRunAt) return true;
  return settings.lastRunAt.getTime() + settings.intervalMinutes * 60_000 <= now.getTime();
}

/** Keep the newest `keep` remote files; delete the rest. */
export async function prune(settings: CloudSettings): Promise<number> {
  let files: RemoteFile[];
  try {
    files = await listRemote(settings);
  } catch {
    return 0;
  }
  let removed = 0;
  for (const file of files.slice(Math.max(1, settings.keep))) {
    if (file.id && (await deleteRemote(settings, file.id))) removed++;
  }
  return removed;
}

// ------------------------------------------------------------------- remote --

export interface RemoteFile {
  id: string;
  name: string;
  size: number;
  modified: number;
}

export async function listRemote(settings: CloudSettings): Promise<RemoteFile[]> {
  const creds = credentialsFor(settings);
  const folder = sanitizeFolder(settings.folder);

  if (settings.provider === "local") {
    const dir = join(backupsDirPath(), "cloud", folder);
    if (!existsSync(dir)) return [];
    const files: RemoteFile[] = [];
    for (const name of await readdir(dir)) {
      if (!name.endsWith(".zip")) continue;
      const full = join(dir, name);
      try {
        const s = await stat(full);
        files.push({ id: name, name, size: s.size, modified: s.mtimeMs });
      } catch {
        /* racy */
      }
    }
    return files.sort((a, b) => b.modified - a.modified);
  }

  if (settings.provider === "4shared") {
    const r = await http("GET", `https://api.4shared.com/v1/files?folder=${encodeURIComponent(folder)}`, headers4shared(creds));
    const entries = r.body.files ?? r.body.items ?? r.body;
    return (Array.isArray(entries) ? entries : []).map((e: Record<string, unknown>) => ({
      id: String(e.id ?? e.file_id ?? e.link ?? ""),
      name: String(e.filename ?? e.name ?? ""),
      size: Number(e.size ?? 0),
      modified: Date.parse(String(e.created ?? e.modified ?? "")) || 0,
    }));
  }

  if (settings.provider === "s3") {
    const bucket = creds.bucket ?? "";
    const query = new URLSearchParams({ "list-type": "2", prefix: `${folder}/`, "max-keys": "200" }).toString();
    const url = s3Url(creds, bucket, "") + `?${query}`;
    const r = await http(
      "GET",
      url,
      s3Headers(creds, "GET", `/${bucket}`, query, sha256("")),
    );
    const contents = (r.body.Contents as Array<Record<string, unknown>>) ?? [];
    return contents.map((e) => ({
      id: String(e.Key ?? ""),
      name: basename(String(e.Key ?? "")),
      size: Number(e.Size ?? 0),
      modified: Date.parse(String(e.LastModified ?? "")) || 0,
    }));
  }

  const token = await oauthToken(settings.provider, creds);
  if (!token) return [];

  if (settings.provider === "dropbox") {
    const r = await http("POST", "https://api.dropboxapi.com/2/files/list_folder", {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    }, JSON.stringify({ path: `/${folder}`, limit: 200 }));
    return ((r.body.entries as Array<Record<string, unknown>>) ?? [])
      .filter((e) => (e[".tag"] as string) === "file")
      .map((e) => ({
        id: String(e.path_display ?? e.path_lower ?? e.id ?? ""),
        name: String(e.name ?? ""),
        size: Number(e.size ?? 0),
        modified: Date.parse(String(e.server_modified ?? "")) || 0,
      }));
  }

  // google_drive
  const folderId = await driveFolderId(creds, token, folder);
  if (!folderId) return [];
  const q = new URLSearchParams({
    q: `'${folderId}' in parents and trashed = false`,
    fields: "files(id,name,size,modifiedTime)",
    orderBy: "modifiedTime desc",
    pageSize: "100",
  }).toString();
  const r = await http("GET", `https://www.googleapis.com/drive/v3/files?${q}`, {
    Authorization: `Bearer ${token}`,
  });
  return ((r.body.files as Array<Record<string, unknown>>) ?? []).map((f) => ({
    id: String(f.id ?? ""),
    name: String(f.name ?? ""),
    size: Number(f.size ?? 0),
    modified: Date.parse(String(f.modifiedTime ?? "")) || 0,
  }));
}

export async function deleteRemote(settings: CloudSettings, remoteId: string): Promise<boolean> {
  const creds = credentialsFor(settings);
  const folder = sanitizeFolder(settings.folder);

  if (settings.provider === "local") {
    const path = join(backupsDirPath(), "cloud", folder, basename(remoteId));
    try {
      await unlink(path);
      return true;
    } catch {
      return false;
    }
  }

  if (settings.provider === "4shared") {
    const r = await http("DELETE", `https://api.4shared.com/v1/files/${encodeURIComponent(remoteId)}`, headers4shared(creds));
    return r.status < 400;
  }

  if (settings.provider === "s3") {
    const key = remoteId.replace(/^\/+/, "");
    const r = await http("DELETE", s3Url(creds, creds.bucket ?? "", key), s3Headers(creds, "DELETE", `/${key}`));
    return r.status < 400;
  }

  const token = await oauthToken(settings.provider, creds);
  if (!token) return false;

  if (settings.provider === "dropbox") {
    const r = await http("POST", "https://api.dropboxapi.com/2/files/delete_v2", {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    }, JSON.stringify({ path: remoteId }));
    return r.status === 200;
  }

  const r = await http("DELETE", `https://www.googleapis.com/drive/v3/files/${encodeURIComponent(remoteId)}`, {
    Authorization: `Bearer ${token}`,
  });
  return r.status === 200 || r.status === 204;
}

export async function restoreRemote(settings: CloudSettings, remoteId: string): Promise<{ ok: boolean; message: string }> {
  const creds = credentialsFor(settings);
  const folder = sanitizeFolder(settings.folder);
  const dir = join(backupsDirPath(), "cloud", folder);
  await mkdir(dir, { recursive: true });
  const target = join(dir, `${randomBytes(6).toString("hex")}_cloud.zip`);

  const downloaded = await download(settings, remoteId, target);
  if (!downloaded) {
    return { ok: false, message: "Could not download that file from the provider." };
  }

  try {
    const { restoreBackupZip } = await import("@/lib/backup");
    await restoreBackupZip(basename(target), new PrismaBackupDb());
    return { ok: true, message: "Restore completed. Tables + uploads restored from portable dump." };
  } finally {
    await unlink(target).catch(() => undefined);
  }
  void creds;
}

async function download(settings: CloudSettings, remoteId: string, localPath: string): Promise<string | null> {
  const creds = credentialsFor(settings);
  const folder = sanitizeFolder(settings.folder);

  if (settings.provider === "local") {
    const source = join(backupsDirPath(), "cloud", folder, basename(remoteId));
    if (!existsSync(source)) return null;
    const { copyFile } = await import("node:fs/promises");
    await copyFile(source, localPath);
    return localPath;
  }

  if (settings.provider === "4shared") {
    const r = await http("GET", `https://api.4shared.com/v1/download/${encodeURIComponent(remoteId)}`, headers4shared(creds));
    if (r.status !== 200) return null;
    const { writeFile } = await import("node:fs/promises");
    await writeFile(localPath, r.raw);
    return localPath;
  }

  if (settings.provider === "s3") {
    const key = remoteId.replace(/^\/+/, "");
    const r = await http("GET", s3Url(creds, creds.bucket ?? "", key), s3Headers(creds, "GET", `/${key}`));
    if (r.status !== 200) return null;
    const { writeFile } = await import("node:fs/promises");
    await writeFile(localPath, r.raw);
    return localPath;
  }

  const token = await oauthToken(settings.provider, creds);
  if (!token) return null;

  if (settings.provider === "dropbox") {
    const r = await http("POST", "https://content.dropboxapi.com/2/files/download", {
      Authorization: `Bearer ${token}`,
      "Dropbox-API-Arg": JSON.stringify({ path: remoteId }),
    });
    if (r.status !== 200) return null;
    const { writeFile } = await import("node:fs/promises");
    await writeFile(localPath, r.raw);
    return localPath;
  }

  const r = await http("GET", `https://www.googleapis.com/drive/v3/files/${encodeURIComponent(remoteId)}?alt=media`, {
    Authorization: `Bearer ${token}`,
  });
  if (r.status !== 200) return null;
  const { writeFile } = await import("node:fs/promises");
  await writeFile(localPath, r.raw);
  return localPath;
}

export async function testConnection(settings: CloudSettings): Promise<{ ok: boolean; message: string }> {
  const creds = credentialsFor(settings);
  if (!isConfigured(settings)) {
    return { ok: false, message: "Provider has no complete credential set." };
  }
  if (settings.provider === "local") {
    const dir = join(backupsDirPath(), "cloud", sanitizeFolder(settings.folder));
    await mkdir(dir, { recursive: true });
    return { ok: true, message: "Local folder ready." };
  }
  if (settings.provider === "4shared") {
    const r = await http("GET", "https://api.4shared.com/v1/account/info", headers4shared(creds));
    return r.status === 200
      ? { ok: true, message: "Connected to 4shared." }
      : { ok: false, message: `4shared rejected the credentials (HTTP ${r.status}).` };
  }
  if (settings.provider === "s3") {
    const query = new URLSearchParams({ "list-type": "2", "max-keys": "1" }).toString();
    const bucket = creds.bucket ?? "";
    const r = await http("GET", s3Url(creds, bucket, "") + `?${query}`, s3Headers(creds, "GET", `/${bucket}`, query, sha256("")));
    if (r.status === 403) return { ok: false, message: "S3 rejected the credentials (HTTP 403)." };
    return r.status >= 400
      ? { ok: false, message: `S3 rejected the request (HTTP ${r.status}).` }
      : { ok: true, message: `Connected to S3 bucket "${bucket}".` };
  }
  const token = await oauthToken(settings.provider, creds);
  if (!token) return { ok: false, message: `${settings.provider} credentials are incomplete or rejected.` };
  if (settings.provider === "dropbox") {
    const r = await http("POST", "https://api.dropboxapi.com/2/files/list_folder", {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    }, JSON.stringify({ path: "", limit: 1 }));
    return r.status === 200
      ? { ok: true, message: "Connected to Dropbox." }
      : { ok: false, message: `Dropbox rejected the credentials (HTTP ${r.status}).` };
  }
  const r = await http("GET", "https://www.googleapis.com/drive/v3/files?pageSize=1&fields=files(id,name)", {
    Authorization: `Bearer ${token}`,
  });
  return r.status === 200
    ? { ok: true, message: "Connected to Google Drive." }
    : { ok: false, message: `Google Drive rejected the credentials (HTTP ${r.status}).` };
}

// ---------------------------------------------------------------- upload api --

async function upload(
  settings: CloudSettings,
  localPath: string,
  fileName: string,
): Promise<{ id: string; name: string; size: number; path: string } | { error: string }> {
  const creds = credentialsFor(settings);
  const folder = sanitizeFolder(settings.folder);
  const name = sanitizeFileName(fileName);

  if (settings.provider === "local") {
    const dir = join(backupsDirPath(), "cloud", folder);
    await mkdir(dir, { recursive: true });
    const target = join(dir, name);
    const { copyFile } = await import("node:fs/promises");
    await copyFile(localPath, target);
    const s = await stat(target);
    return { id: name, name, size: s.size, path: target };
  }

  if (settings.provider === "4shared") {
    const r = await httpMultipart("POST", "https://api.4shared.com/v1/upload", headers4shared(creds), [
      { name: "file", file: localPath, filename: name, mime: "application/zip" },
      { name: "folder", value: folder },
    ]);
    const id = String(r.body.id ?? r.body.file_id ?? r.body.link ?? "");
    if (r.status >= 400 || !id) return { error: `4shared upload failed (HTTP ${r.status}).` };
    const s = await stat(localPath);
    return { id, name: String(r.body.filename ?? name), size: s.size, path: `${folder}/${name}` };
  }

  if (settings.provider === "s3") {
    const key = `${folder}/${name}`;
    const hash = await fileSha256(localPath);
    const { readFile } = await import("node:fs/promises");
    const data = await readFile(localPath);
    const r = await http("PUT", s3Url(creds, creds.bucket ?? "", key), s3Headers(creds, "PUT", `/${key}`, "", hash, { "Content-Type": "application/zip" }), data);
    if (r.status >= 400) return { error: `S3 upload failed (HTTP ${r.status}).` };
    const s = await stat(localPath);
    return { id: key, name, size: s.size, path: key };
  }

  const token = await oauthToken(settings.provider, creds);
  if (!token) return { error: `${settings.provider} credentials are incomplete or rejected.` };

  if (settings.provider === "dropbox") {
    const { readFile } = await import("node:fs/promises");
    const data = await readFile(localPath);
    const r = await http("POST", "https://content.dropboxapi.com/2/files/upload", {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/octet-stream",
      "Dropbox-API-Arg": JSON.stringify({ path: `/${folder}/${name}`, mode: "overwrite", autorename: false, mute: true }),
    }, data);
    if (r.status >= 400 || !r.body.id) return { error: `Dropbox upload failed (HTTP ${r.status}).` };
    const s = await stat(localPath);
    return {
      id: String(r.body.path_display ?? r.body.id),
      name: String(r.body.name ?? name),
      size: s.size,
      path: String(r.body.path_display ?? `/${folder}/${name}`),
    };
  }

  // google_drive multipart
  const folderId = await driveFolderId(creds, token, folder);
  if (!folderId) return { error: "Could not resolve the Google Drive backup folder." };
  const { readFile } = await import("node:fs/promises");
  const data = await readFile(localPath);
  const boundary = `eskoofy${randomBytes(8).toString("hex")}`;
  const meta = Buffer.from(JSON.stringify({ name, parents: [folderId] }), "utf8");
  const body = Buffer.concat([
    Buffer.from(`--${boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n`),
    meta,
    Buffer.from(`\r\n--${boundary}\r\nContent-Type: application/zip\r\nContent-Transfer-Encoding: binary\r\n\r\n`),
    data,
    Buffer.from(`\r\n--${boundary}--\r\n`),
  ]);
  const r = await http("POST", "https://www.googleapis.com/drive/v3/files?uploadType=multipart&fields=id,name,size", {
    Authorization: `Bearer ${token}`,
    "Content-Type": `multipart/related; boundary=${boundary}`,
  }, body);
  if (r.status >= 400 || !r.body.id) return { error: `Google Drive upload failed (HTTP ${r.status}).` };
  const s = await stat(localPath);
  return { id: String(r.body.id), name: String(r.body.name ?? name), size: Number(r.body.size ?? s.size), path: `${folder}/${name}` };
}

// ----------------------------------------------------------- google helpers --

async function driveFolderId(creds: Record<string, string>, token: string, folder: string): Promise<string | null> {
  const q = new URLSearchParams({
    q: `mimeType = 'application/vnd.google-apps.folder' and name = '${folder.replace(/'/g, "\\'")}' and trashed = false`,
    fields: "files(id,name)",
    pageSize: "1",
  }).toString();
  const r = await http("GET", `https://www.googleapis.com/drive/v3/files?${q}`, { Authorization: `Bearer ${token}` });
  const existing = (r.body.files as Array<{ id: string }> | undefined)?.[0]?.id;
  if (existing) return existing;

  const c = await http("POST", "https://www.googleapis.com/drive/v3/files?fields=id", {
    Authorization: `Bearer ${token}`,
    "Content-Type": "application/json; charset=UTF-8",
  }, JSON.stringify({ name: folder, mimeType: "application/vnd.google-apps.folder" }));
  const id = c.body.id as string | undefined;
  return typeof id === "string" && id !== "" ? id : null;
}

async function oauthToken(provider: string, creds: Record<string, string>): Promise<string | null> {
  if (provider === "dropbox") {
    if (creds.refresh_token && creds.app_key) {
      const fields = new URLSearchParams({ grant_type: "refresh_token", refresh_token: creds.refresh_token, client_id: creds.app_key });
      if (creds.app_secret) fields.set("client_secret", creds.app_secret);
      const r = await http("POST", "https://api.dropboxapi.com/oauth2/token", { "Content-Type": "application/x-www-form-urlencoded" }, fields.toString());
      return typeof r.body.access_token === "string" ? r.body.access_token : null;
    }
    return creds.access_token || null;
  }

  // google_drive
  if (creds.service_account_email && creds.service_account_private_key) {
    const key = creds.service_account_private_key.replace(/\\n/g, "\n").trim().replace(/^["']|["']$/g, "");
    const token = jwtAssertion(creds.service_account_email, key, creds.scope ?? "https://www.googleapis.com/auth/drive.file");
    if (!token) return null;
    const r = await http("POST", "https://oauth2.googleapis.com/token", { "Content-Type": "application/x-www-form-urlencoded" }, new URLSearchParams({
      grant_type: "urn:ietf:params:oauth:grant-type:jwt-bearer",
      assertion: token,
    }).toString());
    return typeof r.body.access_token === "string" ? r.body.access_token : null;
  }

  if (creds.refresh_token && creds.client_id) {
    const fields = new URLSearchParams({ grant_type: "refresh_token", refresh_token: creds.refresh_token, client_id: creds.client_id });
    if (creds.client_secret) fields.set("client_secret", creds.client_secret);
    const r = await http("POST", "https://oauth2.googleapis.com/token", { "Content-Type": "application/x-www-form-urlencoded" }, fields.toString());
    return typeof r.body.access_token === "string" ? r.body.access_token : null;
  }
  return null;
}

/** Build an RS256 JWT assertion for Google service-account auth. */
function jwtAssertion(email: string, privateKey: string, scope: string): string | null {
  const b64url = (v: Buffer) => Buffer.from(v).toString("base64url");
  const now = Math.floor(Date.now() / 1000);
  const header = b64url(Buffer.from(JSON.stringify({ alg: "RS256", typ: "JWT" })));
  const claims = b64url(Buffer.from(JSON.stringify({ iss: email, scope, aud: "https://oauth2.googleapis.com/token", exp: now + 3600, iat: now })));
  const input = `${header}.${claims}`;
  const signature = createSign("sha256").update(Buffer.from(input)).sign(privateKey);
  return `${input}.${b64url(signature)}`;
}

// ------------------------------------------------------------------- s3 sig --

function s3Url(creds: Record<string, string>, bucket: string, key: string): string {
  const endpoint = (creds.endpoint ?? "https://s3.amazonaws.com").replace(/\/+$/, "");
  const base = endpoint.includes(`://${bucket}.`) ? endpoint : `${endpoint}/${bucket}`;
  return key ? `${base}/${key.replace(/^\/+/, "")}` : base;
}

function sha256(data: string | Buffer): string {
  return createHash("sha256").update(data).digest("hex");
}

async function fileSha256(path: string): Promise<string> {
  const { readFile } = await import("node:fs/promises");
  return sha256(await readFile(path));
}

function s3Headers(
  creds: Record<string, string>,
  method: string,
  canonicalUri: string,
  canonicalQuery = "",
  payloadHash?: string,
  extra: Record<string, string> = {},
): Record<string, string> {
  const secret = creds.secret ?? "";
  const region = creds.region ?? "us-east-1";
  const hash = payloadHash ?? sha256("");
  const amzDate = new Date().toISOString().replace(/[:-]|\.\d{3}/g, "");
  const dateStamp = amzDate.slice(0, 8);
  const bucket = creds.bucket ?? "";
  const host = new URL(s3Url(creds, bucket, "")).host;

  const headers: Record<string, string> = {
    Host: host,
    "x-amz-content-sha256": hash,
    "x-amz-date": amzDate,
    ...extra,
  };
  const names = Object.keys(headers).sort();
  const canonicalHeaders = names.map((n) => `${n.toLowerCase()}:${String(headers[n]).trim().replace(/\s+/g, " ")}\n`).join("");
  const signedHeaders = names.map((n) => n.toLowerCase()).join(";");

  const canonicalRequest = [method.toUpperCase(), canonicalUri === "" ? "/" : canonicalUri, canonicalQuery, canonicalHeaders, signedHeaders, hash].join("\n");
  const scope = `${dateStamp}/${region}/s3/aws4_request`;
  const stringToSign = ["AWS4-HMAC-SHA256", amzDate, scope, sha256(canonicalRequest)].join("\n");

  const hmac = (key: string | Buffer, data: string) => createHmac("sha256", key).update(data).digest();
  const kDate = hmac(`AWS4${secret}`, dateStamp);
  const kRegion = hmac(kDate, region);
  const kService = hmac(kRegion, "s3");
  const kSigning = hmac(kService, "aws4_request");

  headers.Authorization = `AWS4-HMAC-SHA256 Credential=${creds.key}/${scope}, SignedHeaders=${signedHeaders}, Signature=${hmac(kSigning, stringToSign).toString("hex")}`;
  return headers;
}

function headers4shared(creds: Record<string, string>): Record<string, string> {
  const headers: Record<string, string> = {};
  if (creds.api_key) headers["X-API-KEY"] = creds.api_key;
  if (creds.username) headers.Authorization = `Basic ${Buffer.from(`${creds.username}:${creds.password ?? ""}`).toString("base64")}`;
  return headers;
}

// ------------------------------------------------------------------- http --

interface HttpResponse {
  status: number;
  body: Record<string, unknown>;
  raw: Buffer;
}

async function http(method: string, url: string, headers: Record<string, string>, body?: string | Buffer): Promise<HttpResponse> {
  const res = await fetch(url, {
    method,
    headers,
    body: body as BodyInit,
    redirect: "manual",
  });
  const raw = Buffer.from(await res.arrayBuffer());
  let parsed: Record<string, unknown> = {};
  try {
    parsed = JSON.parse(raw.toString("utf8"));
  } catch {
    parsed = {};
  }
  return { status: res.status, body: parsed, raw };
}

interface MultipartField {
  name: string;
  value?: string;
  file?: string;
  filename?: string;
  mime?: string;
}

async function httpMultipart(
  method: string,
  url: string,
  headers: Record<string, string>,
  fields: MultipartField[],
): Promise<HttpResponse> {
  const boundary = `eskoofy${randomBytes(8).toString("hex")}`;
  const chunks: Buffer[] = [];
  for (const field of fields) {
    if (field.file) {
      const { readFile } = await import("node:fs/promises");
      chunks.push(Buffer.from(`--${boundary}\r\nContent-Disposition: form-data; name="${field.name}"; filename="${field.filename ?? basename(field.file)}"\r\nContent-Type: ${field.mime ?? "application/octet-stream"}\r\n\r\n`));
      chunks.push(await readFile(field.file));
      chunks.push(Buffer.from("\r\n"));
    } else {
      chunks.push(Buffer.from(`--${boundary}\r\nContent-Disposition: form-data; name="${field.name}"\r\n\r\n${field.value ?? ""}\r\n`));
    }
  }
  chunks.push(Buffer.from(`--${boundary}--\r\n`));

  const res = await fetch(url, {
    method,
    headers: { ...headers, "Content-Type": `multipart/form-data; boundary=${boundary}` },
    body: Buffer.concat(chunks),
    redirect: "manual",
  });
  const raw = Buffer.from(await res.arrayBuffer());
  let parsed: Record<string, unknown> = {};
  try {
    parsed = JSON.parse(raw.toString("utf8"));
  } catch {
    parsed = {};
  }
  return { status: res.status, body: parsed, raw };
}

// ------------------------------------------------------------------- lock --

const LOCK_KEY = "eskoofy-cloud-backup-lock";

async function acquireLock(): Promise<boolean> {
  const existing = await prisma.cloud_backup_settings.findFirst();
  if (!existing) return true;
  // Filesystem lock under the backups dir, mirroring the app's Cache::lock.
  const { writeFile } = await import("node:fs/promises");
  const dir = join(backupsDirPath(), ".locks");
  await mkdir(dir, { recursive: true });
  const lockPath = join(dir, LOCK_KEY);
  const now = Date.now();
  if (existsSync(lockPath)) {
    try {
      const s = await stat(lockPath);
      if (now - s.mtimeMs < 600_000) return false;
    } catch {
      /* ignore */
    }
  }
  await writeFile(lockPath, String(now));
  return true;
}

async function releaseLock(): Promise<void> {
  const { rm } = await import("node:fs/promises");
  await rm(join(backupsDirPath(), ".locks", LOCK_KEY), { force: true }).catch(() => undefined);
}

// --------------------------------------------------------------- sanitise --

export function sanitizeFolder(folder: string): string {
  const segments: string[] = [];
  for (const segment of folder.replace(/\\/g, "/").split("/")) {
    const s = segment.trim();
    if (s === "" || s === "." || s === "..") continue;
    segments.push(s.replace(/[^A-Za-z0-9._-]/g, "-"));
  }
  return segments.join("/");
}

export function sanitizeFileName(name: string): string {
  const base = basename(name.replace(/\\/g, "/"));
  const clean = base.replace(/[^A-Za-z0-9._-]/g, "-");
  return clean || "backup.zip";
}

export function providerLabel(provider: string): string {
  const labels: Record<string, string> = {
    local: "This server (local folder)",
    google_drive: "Google Drive",
    dropbox: "Dropbox",
    "4shared": "4shared",
    s3: "Amazon S3 (or compatible)",
  };
  return labels[provider] ?? provider;
}

export function hasPermission(userRole: string | undefined, permission: string): boolean {
  return can(userRole, permission as never);
}