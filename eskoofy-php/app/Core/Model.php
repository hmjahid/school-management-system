<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Support\Carbon;

/**
 * Lightweight Eloquent-like base model so Blade views ported from eskoofy-app
 * can use $model->attribute, relationships and scopes. Instances also implement
 * ArrayAccess so the existing raw-PHP controllers that read $row['col'] keep
 * working unchanged.
 */
class Model implements \ArrayAccess, \JsonSerializable, \IteratorAggregate
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;
    protected static bool $timestamps = true;

    protected array $attributes = [];
    protected array $casts = [];
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $appends = [];

    /** @var array<string,mixed> eager/loaded relations */
    protected array $relations = [];

    /** @var array<string,bool> */
    protected array $loadedRelations = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public static function newFromRow(array $row): static
    {
        return new static($row);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,static>
     */
    public static function hydrate(array $rows): array
    {
        return array_map(static fn ($row) => new static($row), $rows);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public function getTable(): string
    {
        return static::$table;
    }

    public function getKeyName(): string
    {
        return static::$primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    public function getRouteKey(): mixed
    {
        return $this->getKey();
    }

    public function __toString(): string
    {
        return (string) $this->getKey();
    }

    public static function db(): DatabaseInterface
    {
        return Database::getInstance();
    }

    public static function query(): QueryBuilder
    {
        return (new QueryBuilder(static::$table, static::$primaryKey))->setModel(static::class);
    }

    public static function find(mixed $id): ?static
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            http_response_code(404);
            throw new \RuntimeException(static::class . " [{$id}] not found");
        }
        return $model;
    }

    public static function where(string $column, mixed $value, mixed $op = '='): QueryBuilder
    {
        return static::query()->where($column, $value, $op);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return (int) static::query()->whereRaw($where, $params)->count();
    }

    public static function create(array $data): static
    {
        $model = new static($data);
        $model->save();
        return $model;
    }

    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $query = static::query();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $found = $query->first();
        if ($found !== null) {
            return $found;
        }
        return static::create(array_merge($attributes, $values));
    }

    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $model = static::firstOrCreate($attributes);
        if ($values !== []) {
            $model->fill($values);
            $model->save();
        }
        return $model;
    }

    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function save(): bool
    {
        $data = $this->attributes;
        if (static::$timestamps) {
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->attributes['updated_at'] = $data['updated_at'];
        }
        $id = $this->attributes[static::$primaryKey] ?? null;
        if ($id) {
            unset($data[static::$primaryKey]);
            static::db()->update(static::$table, $data, static::$primaryKey . ' = ?', [$id]);
        } else {
            unset($data[static::$primaryKey]);
            $newId = static::db()->insert(static::$table, $data);
            $this->attributes[static::$primaryKey] = $newId;
        }
        return true;
    }

    public function update(array $data = []): bool
    {
        $this->fill($data);
        return $this->save();
    }

    public function delete(): bool
    {
        $id = $this->getKey();
        if (static::$softDeletes) {
            static::db()->update(static::$table, ['deleted_at' => date('Y-m-d H:i:s')], static::$primaryKey . ' = ?', [$id]);
        } else {
            static::db()->delete(static::$table, static::$primaryKey . ' = ?', [$id]);
        }
        return true;
    }

    public function fresh(): ?static
    {
        return static::find($this->getKey());
    }

    // ------------------------------------------------------------------
    // Attribute access / casts
    // ------------------------------------------------------------------

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        // Accessor: getFooAttribute
        $accessor = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->{$accessor}($this->attributes[$key] ?? null);
        }

        // Relationship method (lazy-load + cache).
        if (method_exists($this, $key)) {
            $result = $this->{$key}();
            if ($result instanceof Relation) {
                $this->relations[$key] = $result->getResults();
                $this->loadedRelations[$key] = true;
                return $this->relations[$key];
            }
            if ($result instanceof QueryBuilder) {
                $this->relations[$key] = $result->get();
                return $this->relations[$key];
            }
        }

        // Snake-case relation fallback ($model->createdBy -> created_by_id etc.)
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));
        if ($snake !== $key && method_exists($this, $snake)) {
            $result = $this->{$snake}();
            if ($result instanceof Relation) {
                $this->relations[$key] = $result->getResults();
                return $this->relations[$key];
            }
        }

        return null;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        $type = $this->casts[$key] ?? null;
        if ($value === null || $type === null) {
            return $value;
        }
        if (str_contains($type, ':')) {
            [$type, $format] = explode(':', $type, 2);
        }
        return match ($type) {
            'int', 'integer'          => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'decimal'                 => (float) $value,
            'string'                  => (string) $value,
            'bool', 'boolean'         => (bool) $value,
            'array', 'json', 'object' => is_array($value) ? $value : (json_decode((string) $value, true) ?? []),
            'date', 'datetime', 'timestamp', 'immutable_date', 'immutable_datetime'
                => $value instanceof Carbon ? $value
                    : (($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00' || $value === null)
                        ? null
                        : new Carbon($value)),
            default                   => $value,
        };
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes)
            || array_key_exists($key, $this->relations)
            || method_exists($this, $key);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * Local query scopes + relation proxies: $model->scopeX() or static::published().
     */
    public function __call(string $method, array $parameters): mixed
    {
        $scope = 'scope' . ucfirst($method);
        if (method_exists($this, $scope)) {
            return $this->{$scope}(static::query(), ...$parameters);
        }
        if (method_exists($this, $method)) {
            return $this->{$method}(...$parameters);
        }
        return null;
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        $instance = new static();
        $scope = 'scope' . ucfirst($method);
        if (method_exists($instance, $scope)) {
            return $instance->{$scope}(static::query(), ...$parameters);
        }
        // Pass through to the query builder (whereX, orderBy, etc.)
        return static::query()->{$method}(...$parameters);
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Relation
    {
        $foreignKey = $foreignKey ?? $this->guessForeignKey($related);
        $ownerKey = $ownerKey ?? (new $related())->getKeyName();
        $value = $this->attributes[$foreignKey] ?? null;
        return new Relation($related::query()->where($ownerKey, $value), 'one');
    }

    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey = $foreignKey ?? $this->guessMorphForeignKey();
        $localKey = $localKey ?? $this->getKeyName();
        return new Relation($related::query()->where($foreignKey, $this->attributes[$localKey] ?? null), 'one');
    }

    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey = $foreignKey ?? $this->guessMorphForeignKey();
        $localKey = $localKey ?? $this->getKeyName();
        return new Relation($related::query()->where($foreignKey, $this->attributes[$localKey] ?? null), 'many');
    }

    protected function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): Relation
    {
        $instance = new $related();
        $foreignPivotKey = $foreignPivotKey ?? strtolower(class_basename(static::class)) . '_id';
        $relatedPivotKey = $relatedPivotKey ?? strtolower(class_basename($related)) . '_id';
        $pivot = $table ?? $this->guessPivotTable($related);
        $sql = "SELECT related.* FROM {$instance->getTable()} related
                JOIN {$pivot} pivot ON pivot.{$relatedPivotKey} = related.{$instance->getKeyName()}
                WHERE pivot.{$foreignPivotKey} = ?";
        $rows = static::db()->fetchAll($sql, [$this->getKey()]);
        $models = array_map(static fn ($row) => $instance::newFromRow($row), $rows);
        return new Relation(null, 'many', $models);
    }

    protected function guessForeignKey(string $related): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', class_basename($related))) . '_id';
    }

    protected function guessMorphForeignKey(): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', class_basename(static::class))) . '_id';
    }

    protected function guessPivotTable(string $related): string
    {
        $parts = [strtolower(class_basename(static::class)), strtolower(class_basename($related))];
        sort($parts);
        return implode('_', $parts);
    }

    public function load(string ...$relations): static
    {
        foreach ($relations as $relation) {
            $this->getAttribute($relation);
        }
        return $this;
    }

    public function relationLoaded(string $key): bool
    {
        return isset($this->loadedRelations[$key]);
    }

    public function setRelation(string $key, mixed $value): static
    {
        $this->relations[$key] = $value;
        $this->loadedRelations[$key] = true;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    // ------------------------------------------------------------------
    // Serialization / ArrayAccess
    // ------------------------------------------------------------------

    public function toArray(): array
    {
        $data = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($data[$key]);
        }
        foreach ($data as $key => $value) {
            $data[$key] = $this->castAttribute($key, $value);
        }
        foreach ($this->appends as $key) {
            $data[$key] = $this->getAttribute($key);
        }
        foreach ($this->relations as $key => $value) {
            if ($value instanceof self) {
                $data[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $data[$key] = array_map(static fn ($v) => $v instanceof self ? $v->toArray() : $v, $value);
            }
        }
        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->attributes) || array_key_exists($offset, $this->relations);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }
}
