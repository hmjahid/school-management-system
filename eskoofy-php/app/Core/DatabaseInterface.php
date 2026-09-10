<?php
declare(strict_types=1);

namespace App\Core;

/**
 * The minimum surface of the database layer the controllers, models and
 * services rely on. Concrete implementation is {@see Database} (PDO/MySQL);
 * tests inject a fake via {@see Database::setInstance()} without touching PDO.
 */
interface DatabaseInterface
{
    public function query(string $sql, array $params = []): \PDOStatement;

    public function fetch(string $sql, array $params = []): ?array;

    public function fetchAll(string $sql, array $params = []): array;

    public function insert(string $table, array $data): int;

    public function update(string $table, array $data, string $where, array $whereParams = []): int;

    public function delete(string $table, string $where, array $params = []): int;

    public function count(string $table, string $where = '1=1', array $params = []): int;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;
}