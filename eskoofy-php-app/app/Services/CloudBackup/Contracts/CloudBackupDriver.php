<?php

declare(strict_types=1);

namespace App\Services\CloudBackup\Contracts;

/**
 * Every cloud provider implements these four verbs. The manager only ever
 * talks to a driver through this interface, so adding a provider means adding
 * one class — no changes to the manager, commands, or the admin UI.
 *
 * A driver MUST NOT throw from test()/upload()/download()/delete(); it returns
 * a status array or false so the run log can record the failure instead.
 */
interface CloudBackupDriver
{
    /** Provider key, e.g. "google_drive". */
    public function key(): string;

    /** Human label for the settings form. */
    public function label(): string;

    /**
     * Credential check. Never throws.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(): array;

    /**
     * Upload a local portable-backup zip.
     *
     * @return array{id: string, name: string, size: int, path: string}|array{error: string}
     */
    public function upload(string $localPath, string $fileName): array;

    /**
     * The single value that identifies an uploaded file for every later
     * download()/delete() call, extracted from an upload()/list() result.
     *
     * @param  array<string, mixed>  $result
     */
    public function remoteKey(array $result): string;

    /**
     * Download a remote file to a local path. Returns the local path or null.
     */
    public function download(string $remoteId, string $localPath): ?string;

    /** Delete a remote file. */
    public function delete(string $remoteId): bool;

    /**
     * List remote files, newest first.
     *
     * @return array<int, array{id: string, name: string, size: int, modified: int}>
     */
    public function list(): array;
}