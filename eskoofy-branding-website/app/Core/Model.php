<?php
declare(strict_types=1);

namespace App\Core;

class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;
    protected array $attributes = [];
    protected array $casts = [];
    protected array $fillable = [];
    protected array $hidden = [];

    private static ?DatabaseInterface $testDb = null;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Test-only: swap the active database for a fake so unit tests can exercise
     * models without a real MySQL connection. Pass null to clear.
     */
    public static function setTestDatabase(?DatabaseInterface $db): void
    {
        self::$testDb = $db;
    }

    public static function db(): DatabaseInterface
    {
        return self::$testDb ?? Database::getInstance();
    }

    public static function find(int $id): ?static
    {
        $row = static::db()->fetch(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?",
            [$id]
        );
        return $row ? new static($row) : null;
    }

    public static function where(string $column, mixed $value, string $op = '='): static
    {
        $instance = new static();
        $instance->queryWhere = "{$column} {$op} ?";
        $instance->queryParams = [$value];
        return $instance;
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table, static::$primaryKey);
    }

    public function save(): bool
    {
        $data = $this->toArray();
        if (isset($this->attributes[static::$primaryKey]) && $this->attributes[static::$primaryKey]) {
            $id = $this->attributes[static::$primaryKey];
            unset($data[static::$primaryKey]);
            static::db()->update(static::$table, $data, static::$primaryKey . ' = ?', [$id]);
        } else {
            unset($data[static::$primaryKey]);
            $id = static::db()->insert(static::$table, $data);
            $this->attributes[static::$primaryKey] = $id;
        }
        return true;
    }

    public function delete(): bool
    {
        if (static::$softDeletes) {
            static::db()->update(static::$table, ['deleted_at' => date('Y-m-d H:i:s')], static::$primaryKey . ' = ?', [$this->id]);
        } else {
            static::db()->delete(static::$table, static::$primaryKey . ' = ?', [$this->id]);
        }
        return true;
    }

    public static function create(array $data): static
    {
        $model = new static($data);
        $model->save();
        return $model;
    }

    public static function all(): array
    {
        $rows = static::db()->fetchAll("SELECT * FROM " . static::$table . " WHERE deleted_at IS NULL");
        return array_map(fn($row) => new static($row), $rows);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return static::db()->count(static::$table, $where . ' AND deleted_at IS NULL', $params);
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        $data = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($data[$key]);
        }
        foreach ($this->casts as $key => $type) {
            if (!isset($data[$key])) continue;
            switch ($type) {
                case 'boolean': $data[$key] = (bool) $data[$key]; break;
                case 'integer': $data[$key] = (int) $data[$key]; break;
                case 'float':   $data[$key] = (float) $data[$key]; break;
                case 'json':    $data[$key] = json_decode($data[$key], true); break;
            }
        }
        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
