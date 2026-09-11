<?php
declare(strict_types=1);

namespace App\Core\Support;

/**
 * Minimal LengthAwarePaginator so Blade views can render $paginator->links()
 * using the ported vendor/pagination views.
 */
class LengthAwarePaginator
{
    protected array $items;
    protected int $total;
    protected int $perPage;
    protected int $currentPage;
    protected string $pageName = 'page';
    protected array $query = [];
    protected string $fragment = '';

    public function __construct(array|\App\Core\Support\Collection|\Traversable $items, int $total, int $perPage, int $currentPage, array $options = [])
    {
        if ($items instanceof \App\Core\Support\Collection) {
            $items = $items->all();
        } elseif ($items instanceof \Traversable) {
            $items = iterator_to_array($items);
        }
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->pageName = $options['pageName'] ?? 'page';
        $this->query = $_GET ?? [];
    }

    public static function fromQuery(array $paginated, string $pageName = 'page'): static
    {
        return new static(
            $paginated['data'],
            (int) $paginated['total'],
            (int) $paginated['per_page'],
            (int) $paginated['current_page'],
            ['pageName' => $pageName]
        );
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    public function firstItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    public function lastItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }
        return min($this->currentPage * $this->perPage, $this->total);
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function withQueryString(): static
    {
        $clone = clone $this;
        return $clone;
    }

    public function url(int $page): string
    {
        $query = $this->query;
        $query[$this->pageName] = $page;
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
        return $path . '?' . http_build_query($query);
    }

    public function previousPageUrl(): ?string
    {
        return $this->currentPage > 1 ? $this->url($this->currentPage - 1) : null;
    }

    public function nextPageUrl(): ?string
    {
        return $this->hasMorePages() ? $this->url($this->currentPage + 1) : null;
    }

    public function elements(): array
    {
        $elements = [];
        $lastPage = $this->lastPage();
        $current = $this->currentPage;
        $start = max(1, $current - 2);
        $end = min($lastPage, $current + 2);
        if ($start > 1) {
            $elements[] = 1;
            if ($start > 2) {
                $elements[] = '...';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $elements[] = $i;
        }
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $elements[] = '...';
            }
            $elements[] = $lastPage;
        }
        return $elements;
    }

    public function getUrlRange(int $start, int $end): array
    {
        $urls = [];
        for ($i = $start; $i <= $end; $i++) {
            $urls[$i] = $this->url($i);
        }
        return $urls;
    }

    public function links(?string $view = null): string
    {
        $view ??= 'vendor.pagination.tailwind';
        $view = str_replace('pagination.', 'pagination/', $view);
        if (str_contains($view, 'tailwind')) {
            $view = 'vendor/pagination/tailwind';
        }
        $data = [
            'paginator' => $this,
            'elements'  => $this->elements(),
        ];
        return \App\Core\Blade::render($view, $data);
    }

    public function getCollection(): Collection
    {
        return new Collection($this->items);
    }
}