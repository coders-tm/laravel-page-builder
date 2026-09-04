<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Registry;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\File;

/**
 * Abstract base for schema registries that discover and cache schemas
 * from registered Blade file paths.
 *
 * The last registration for a given type wins, allowing themes to
 * shadow built-in definitions.
 *
 * @template TSchema of Arrayable
 */
abstract class BaseRegistry
{
    /**
     * Registered paths (simple strings or associative arrays).
     *
     * @var array<int, string|array{path: string, namespace?: string}>
     */
    protected array $paths = [];

    /**
     * Cached resolved entries.
     *
     * @var array<string, RegistryEntry>|null
     */
    protected ?array $items = null;

    public function __construct(
        protected readonly SchemaExtractor $extractor,
    ) {}

    /**
     * Get the view subdirectory name (e.g. 'sections' or 'blocks').
     */
    abstract protected function viewPrefix(): string;

    /**
     * Create a schema instance from raw extracted data.
     *
     * @return TSchema
     */
    abstract protected function createSchema(string $type, array $rawSchema): object;

    /**
     * Hook for subclasses to modify raw schema data before creating the schema object.
     * Called after extraction, before createSchema().
     */
    protected function prepareRawSchema(string $type, array $rawSchema): array
    {
        return $rawSchema;
    }

    /**
     * Register a path (or paths) with an optional view namespace.
     *
     *   $registry->add('/path/to/views');
     *   $registry->add(['path' => '/path/to/theme/views', 'namespace' => 'my-theme']);
     */
    public function add(string|array $paths): void
    {
        $entries = is_array($paths) && isset($paths['path'])
            ? [$paths]
            : (array) $paths;

        foreach ($entries as $entry) {
            $directory = is_array($entry) ? ($entry['path'] ?? '') : $entry;

            // Deduplicate by resolved directory path
            foreach ($this->paths as $existing) {
                $existingDir = is_array($existing) ? ($existing['path'] ?? '') : $existing;
                if ($existingDir === $directory) {
                    continue 2;
                }
            }

            $this->paths[] = $entry;
        }

        // Invalidate cache when paths change
        $this->items = null;
    }

    /**
     * Register a schema directly by type.
     *
     * @param  TSchema  $schema
     */
    public function register(string $type, object $schema, ?string $view = null): void
    {
        $this->resolve();

        $this->items[$type] = new RegistryEntry(
            type: $type,
            view: $view ?? "{$this->viewPrefix()}.{$type}",
            schema: $schema,
        );
    }

    /**
     * Get a specific entry by type, or all entries.
     */
    public function get(?string $type = null): RegistryEntry|array|null
    {
        $this->resolve();

        if ($type !== null) {
            return $this->items[$type] ?? null;
        }

        return $this->items;
    }

    /**
     * Check if a type exists.
     */
    public function has(string $type): bool
    {
        return ! empty($this->get($type));
    }

    /**
     * Get all registered types.
     *
     * @return array<string>
     */
    public function types(): array
    {
        return array_keys($this->get() ?? []);
    }

    /**
     * Resolve entries from all registered paths (lazy-loaded).
     */
    protected function resolve(): void
    {
        if ($this->items !== null) {
            return;
        }

        $this->items = [];
        $prefix = $this->viewPrefix();

        foreach ($this->paths as $entry) {
            [$directory, $namespace] = $this->resolveEntry($entry);

            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::glob($directory.'/*.blade.php') as $filePath) {
                $filename = basename($filePath, '.blade.php');
                $type = $filename;
                $viewName = $namespace
                    ? "{$namespace}::{$prefix}.{$filename}"
                    : "{$prefix}.{$filename}";

                $rawSchema = $this->extractor->extract($filePath);

                if ($rawSchema === null) {
                    continue;
                }

                $rawSchema = $this->prepareRawSchema($type, $rawSchema);
                $schema = $this->createSchema($type, $rawSchema);

                // Later registrations (themes) override earlier ones.
                $this->items[$type] = new RegistryEntry(
                    type: $type,
                    view: $viewName,
                    schema: $schema,
                );
            }
        }
    }

    /**
     * Normalise a path entry into [directory, namespace|null].
     *
     * @return array{string, string|null}
     */
    protected function resolveEntry(string|array $entry): array
    {
        if (is_string($entry)) {
            return [$entry, null];
        }

        return [$entry['path'], $entry['namespace'] ?? null];
    }
}
