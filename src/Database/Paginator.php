<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Simple paginator for database query results.
 *
 * Encapsulates pagination metadata and provides convenience methods
 * for navigating through paged result sets. Implements JsonSerializable
 * for easy API response integration.
 */
final class Paginator implements \JsonSerializable
{
    /**
     * @param array<int, mixed> $items Items for the current page
     * @param int $total Total number of items across all pages
     * @param int $perPage Number of items per page
     * @param int $currentPage Current page number (1-based)
     */
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $currentPage,
    ) {
    }

    /**
     * Get the items for the current page.
     *
     * @return array<int, mixed>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get the total number of items.
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get the number of items per page.
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the current page number.
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get the last (maximum) page number.
     */
    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    /**
     * Determine if there are more pages after the current one.
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /**
     * Get the previous page number, or null if on the first page.
     */
    public function previousPage(): ?int
    {
        if ($this->currentPage <= 1) {
            return null;
        }

        return $this->currentPage - 1;
    }

    /**
     * Get the next page number, or null if on the last page.
     */
    public function nextPage(): ?int
    {
        if (!$this->hasMorePages()) {
            return null;
        }

        return $this->currentPage + 1;
    }

    /**
     * Get the 1-based index of the first item on the current page.
     *
     * Returns null if there are no items.
     */
    public function firstItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }

        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * Get the 1-based index of the last item on the current page.
     *
     * Returns null if there are no items.
     */
    public function lastItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }

        $firstItem = $this->firstItem();

        return $firstItem !== null ? $firstItem + count($this->items) - 1 : null;
    }

    /**
     * Convert the paginator to an array.
     *
     * @return array{
     *     data: array<int, mixed>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *     first_item: int|null,
     *     last_item: int|null,
     *     has_more_pages: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage(),
            'first_item' => $this->firstItem(),
            'last_item' => $this->lastItem(),
            'has_more_pages' => $this->hasMorePages(),
        ];
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
