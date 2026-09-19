<?php
declare(strict_types=1);

namespace App\Core;

interface DatabaseInterface
{
    public function fetch(string $sql, array $params = []): ?array;

    public function fetchAll(string $sql, array $params = []): array;

    public function insert(string $table, array $data): int;

    public function update(string $table, array $data, string $where, array $whereParams = []): int;

    public function count(string $table, string $where = '1=1', array $params = []): int;
}