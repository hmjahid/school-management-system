<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Result wrapper for model relationships (belongsTo/hasMany/hasOne).
 */
class Relation
{
    /** @var list<Model>|null */
    protected ?array $preloaded;

    public function __construct(
        protected ?QueryBuilder $query,
        protected string $type = 'many',
        ?array $preloaded = null
    ) {
        $this->preloaded = $preloaded;
    }

    public function getResults(): mixed
    {
        if ($this->preloaded !== null) {
            return $this->type === 'one'
                ? ($this->preloaded[0] ?? null)
                : new \App\Core\Support\Collection($this->preloaded);
        }
        if ($this->query === null) {
            return $this->type === 'one' ? null : new \App\Core\Support\Collection();
        }
        return $this->type === 'one' ? $this->query->first() : $this->query->get();
    }

    public function first(): mixed
    {
        return $this->type === 'one'
            ? $this->getResults()
            : ($this->preloaded[0] ?? ($this->query?->first()));
    }

    public function get(): mixed
    {
        return $this->type === 'one' ? $this->getResults() : $this->getResults();
    }

    public function exists(): bool
    {
        return $this->type === 'one'
            ? $this->getResults() !== null
            : $this->getResults()->isNotEmpty();
    }

    public function count(): int
    {
        $results = $this->getResults();
        if ($this->type === 'one') {
            return $results === null ? 0 : 1;
        }
        return $results instanceof \Countable ? $results->count() : 0;
    }
}
