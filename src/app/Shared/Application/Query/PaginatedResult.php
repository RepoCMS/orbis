<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * Generic paginated result returned by list query handlers.
 *
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param T[]   $items
     */
    public function __construct(
        public array $items,
        public int   $total,
        public int   $page,
        public int   $perPage,
    ) {
    }

    public function totalPages(): int
    {
        if ($this->perPage <= 0 || $this->total === 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}
