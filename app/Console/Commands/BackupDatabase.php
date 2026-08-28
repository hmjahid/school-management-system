<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=7 : Number of backups to retain}';

    protected $description = 'Create a compressed database backup in storage/app/backups and prune old ones';

    protected int $retention;

    protected string $backupDir;

    public function handle(): int
    {
        $this->retention = max(1, (int) $this->option('keep'));
        $this->backupDir = storage_path('app/backups');

        File::ensureDirectoryExists($this->backupDir, 0755);

        $connection = config('database.default');
        $filename = sprintf('%s_%s.sql.gz', $connection, now()->format('Y-m-d_His'));
        $target = $this->backupDir.'/'.$filename;

        $ok = match ($connection) {
            'sqlite' => $this->backupSqlite($target),
            'mysql', 'mariadb', 'pgsql' => $this->backupServer($connection, $target),
            default => $this->failUnknown($connection),
        };

        if (! $ok) {
            $this->error('Backup failed.');

            return self::FAILURE;
        }

        $this->info("Backup written to {$target}");
        $this->prune();

        return self::SUCCESS;
    }

    protected function backupSqlite(string $target): bool
    {
        $path = config('database.connections.sqlite.database');
        $path = $path === ':memory:' ? database_path('database.sqlite') : $path;

        if (! file_exists($path)) {
            $this->error("SQLite database not found at {$path}.");

            return false;
        }

        if (class_exists(\SQLite3::class)) {
            $db = new \SQLite3($path);
            $db->exec('VACUUM INTO '.escapeshellarg($target));
            $db->close();

            return file_exists($target);
        }

        // Fallback: copy the file and journal. Not crash-safe, keep WAL checkpoint first.
        $checkpoint = new Process(['sqlite3', $path, 'PRAGMA wal_checkpoint(TRUNCATE);']);
        $checkpoint->run();

        return copy($path, $target);
    }

    protected function backupServer(string $connection, string $target): bool
    {
        $conf = config("database.connections.{$connection}");

        $cmd = $connection === 'pgsql' ? 'pg_dump' : 'mysqldump';

        $args = array_filter([
            $cmd,
            '--no-tablespaces',
            '--single-transaction',
            $connection === 'pgsql' ? null : '--quick',
            '--host',
            $conf['host'] ?? '127.0.0.1',
            '--port',
            (string) ($conf['port'] ?? ($connection === 'pgsql' ? 5432 : 3306)),
            '-u',
            $conf['username'] ?? '',
        ], fn ($v) => $v !== null);

        $password = $conf['password'] ?? '';
        if ($password !== '') {
            $args[] = '-p'.$password;
        }

        $args[] = $connection === 'pgsql' ? '--dbname='.$conf['database'] : $conf['database'];

        $process = new Process($args);

        $process->run();
        if (! $process->isSuccessful()) {
            $this->error('Dump failed: '.$process->getErrorOutput());

            return false;
        }

        $gzip = new Process(['gzip']);
        $gzip->setInput($process->getOutput());
        $gzip->run();
        if (! $gzip->isSuccessful()) {
            $this->error('gzip failed: '.$gzip->getErrorOutput());

            return false;
        }

        file_put_contents($target, $gzip->getOutput());

        return file_exists($target) && filesize($target) > 0;
    }

    protected function failUnknown(string $connection): bool
    {
        $this->error("Unsupported database connection '{$connection}'. Back up manually.");

        return false;
    }

    protected function prune(): void
    {
        $files = collect(File::files($this->backupDir))
            ->filter(fn ($f) => $f->getExtension() === 'gz')
            ->sortByDesc(fn ($f) => $f->getMTime());

        $files->slice($this->retention)->each(function ($file) {
            File::delete($file->getPathname());
            $this->warn("Pruned old backup {$file->getFilename()}");
        });
    }
}