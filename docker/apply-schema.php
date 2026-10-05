<?php

declare(strict_types=1);

/**
 * Shared, *idempotent* schema applier for the Docker dev harnesses
 * (php-dev, website-dev).
 *
 * WHY THIS EXISTS
 * ---------------
 * Both harnesses re-apply `database/schema.sql` on every container boot, so a
 * schema edit lands on `./docker/dev.sh restart <product>` instead of requiring
 * a wiped data volume. The old entrypoints claimed this was safe because the
 * schema only uses `CREATE TABLE IF NOT EXISTS`. That is true for the CREATE
 * TABLEs and false for the rest of the file: a MariaDB init script runs only on
 * a fresh volume, so on every *subsequent* boot the CREATE TABLEs are no-ops
 * while everything else in the file runs again. One such statement exists
 * today —
 *
 *     ALTER TABLE `batches`
 *       ADD CONSTRAINT `batches_course_id_foreign` FOREIGN KEY ...;
 *
 * (deferred because `courses` is defined after `batches`) — and re-running it
 * aborts the whole file with ER_DUP_CONSTRAINT_NAME (1826). The symptom was not
 * a failed migration, it was the app container crash-looping and refusing to
 * serve anything, which is the worst way to find out you edited a schema file.
 *
 * So: apply statement by statement, and treat "this object is already there" as
 * success. That keeps the property the harness needs (additive schema edits land
 * on restart) without letting a benign duplicate abort the boot.
 *
 * KNOWN LIMITATION: an *existing* named constraint is never modified, only
 * skipped. Changing an existing FK's ON DELETE action needs
 * `./docker/dev.sh restart <product> --volumes` or a manual ALTER. Skipped
 * constraints are printed, so this is never silent.
 *
 * Usage: apply-schema.php [schema.sql]
 *   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD come from the env.
 */

/**
 * Split a SQL file into statements on top-level semicolons.
 *
 * Quote- and comment-aware, because the schemas contain semicolons inside
 * string literals, comments and default values. No DELIMITER / trigger /
 * procedure support by design: the product schemas contain none, and a schema
 * that starts using them must teach this splitter about them rather than being
 * silently truncated.
 *
 * @return list<string>
 */
function esk_split_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $inSingle = $inDouble = $inBacktick = $inLineComment = $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
                $current .= $char;
            }
            continue;
        }
        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }
        if (! $inSingle && ! $inDouble && ! $inBacktick) {
            if (($char === '-' && $next === '-') || $char === '#') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($char === '\\' && ($inSingle || $inDouble)) { // escaped quote
            $current .= $char . $next;
            $i++;
            continue;
        }
        if ($char === "'" && ! $inDouble && ! $inBacktick) {
            $inSingle = ! $inSingle;
        } elseif ($char === '"' && ! $inSingle && ! $inBacktick) {
            $inDouble = ! $inDouble;
        } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
            $inBacktick = ! $inBacktick;
        }

        if ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}

/**
 * Apply a schema file to the database named by the environment.
 *
 * @return int process exit code
 */
function esk_apply_schema(string $file): int
{
    if (! is_file($file)) {
        fwrite(STDERR, "==> No schema.sql found at $file — skipping.\n");

        return 0;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "==> $file is empty — skipping.\n");

        return 0;
    }

    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'db',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_DATABASE') ?: 'eskoofy_website',
        ),
        getenv('DB_USERNAME') ?: 'esk',
        getenv('DB_PASSWORD') ?: 'eskpw',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );

    // A schema that defers an FK until a later table exists relies on FK
    // enforcement being off while the file runs.
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // "The object is already there." Benign on a re-apply; reported either way
    // so a fresh database that hits one is still visible. A local array, not a
    // `const` — constant arrays are only legal at file/class scope.
    $alreadyExists = [
        1007, // ER_DB_ALREADY_EXISTS
        1050, // ER_TABLE_EXISTS_ERROR
        1060, // ER_DUP_FIELDNAME
        1061, // ER_DUP_KEYNAME
        1062, // ER_DUP_ENTRY (a re-inserted seed row is harmless)
        1826, // ER_DUP_CONSTRAINT_NAME  <-- the one that was breaking boot
    ];

    $applied = 0;
    $skipped = [];

    foreach (esk_split_statements($sql) as $statement) {
        try {
            $pdo->exec($statement);
            $applied++;
        } catch (PDOException $e) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if (! in_array($code, $alreadyExists, true)) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                fwrite(STDERR, "\n==> schema.sql FAILED (SQLSTATE {$e->getCode()}, driver $code)\n");
                fwrite(STDERR, '    ' . substr(preg_replace('/\s+/', ' ', $statement), 0, 300) . "\n");
                fwrite(STDERR, '    ' . $e->getMessage() . "\n");

                return 1;
            }
            $skipped[] = $statement;
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo '    ' . basename($file) . ": {$applied} statement(s) applied";
    echo $skipped === [] ? "\n" : ', ' . count($skipped) . " already present (skipped)\n";

    // Never silent: an existing named constraint is not modified, only skipped.
    foreach ($skipped as $statement) {
        $flat = (string) preg_replace('/\s+/', ' ', $statement);
        if (preg_match('/^\s*ALTER\s+TABLE.*\bADD\s+(CONSTRAINT|INDEX|KEY|FOREIGN KEY|PRIMARY|UNIQUE)\b/i', $flat)) {
            echo '    note: already applied, NOT modified -> ' . substr($flat, 0, 160) . "\n";
            echo "          to change it: ./docker/dev.sh restart <product> --volumes\n";
        }
    }

    return 0;
}

// Only run when executed, not when required: the splitter above is unit tested
// against the real schema files without needing a database.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    exit(esk_apply_schema($argv[1] ?? (getenv('ESK_SCHEMA_FILE') ?: '/var/www/database/schema.sql')));
}
