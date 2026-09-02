<?php

declare(strict_types=1);

namespace PageBuilder\Collections;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use Traversable;

/**
 * Abstract ordered collection providing iteration, lookup, and serialization.
 *
 * Subclasses specify the item type and may add domain-specific methods
 * (e.g. render(), enabled()).
 *
 * @template TItem
 *
 * @template-implements ArrayAccess<string, TItem>
 * @template-implements IteratorAggregate<string, TItem>
 */
abstract class BaseCollection implements Arrayable, ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<string, TItem> */
    protected array $items;

    /** @param array<string, TItem> $items */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    // ─── Iteration & Counting ────────────────────────────────────

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
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
        return $this->items !== [];
    }

    /**
     * Return the first item, or null if the collection is empty.
     *
     * @return TItem|null
     */
    public function first(): mixed
    {
        return $this->items === [] ? null : array_values($this->items)[0];
    }

    /**
     * Return the last item, or null if the collection is empty.
     *
     * @return TItem|null
     */
    public function last(): mixed
    {
        return $this->items === [] ? null : array_values($this->items)[count($this->items) - 1];
    }

    /**
     * Return the item at the given zero-based index.
     *
     * @return TItem|null
     */
    public function nth(int $index): mixed
    {
        return array_values($this->items)[$index] ?? null;
    }

    /**
     * Find an item by its ID (string key).
     *
     * @return TItem|null
     */
    public function find(string $id): mixed
    {
        return $this->items[$id] ?? null;
    }

    /**
     * Return a new collection containing only items of the given type.
     */
    public function ofType(string $type): static
    {
        return new static(
            array_filter($this->items, fn ($item) => $item->type === $type)
        );
    }

    /**
     * Apply a callback to each item and return results.
     *
     * @param  callable(TItem, string): mixed  $callback
     * @return array<string, mixed>
     */
    public function map(callable $callback): array
    {
        $result = [];

        foreach ($this->items as $id => $item) {
            $result[$id] = $callback($item, $id);
        }

        return $result;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[(string) $offset]);
    }

    /** @return TItem|null */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[(string) $offset]);
    }

    /**
     * Chunk the collection into multiple collections of a given size.
     *
     * @return array<static>
     */
    public function chunk(int $size): array
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return $chunks;
    }

    /**
     * Take a certain number of items from the collection.
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit, abs($limit), true));
        }

        return new static(array_slice($this->items, 0, $limit, true));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->items as $id => $item) {
            $result[$id] = $item->toArray();
        }

        return $result;
    }
}
