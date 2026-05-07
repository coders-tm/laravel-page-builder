<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Components;

use Coderstm\PageBuilder\Collections\BlockCollection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Abstract base for runtime components (Section, Block) hydrated from page JSON.
 *
 * Subclasses only need to implement `editorAttributes()` to delegate to the
 * appropriate `EditorAttributes` method and optionally override `defaultType()`.
 */
abstract class BaseComponent implements Arrayable, Jsonable, JsonSerializable
{
    public readonly string $id;

    public readonly string $type;

    public readonly string $name;

    public readonly bool $disabled;

    public readonly Settings $settings;

    public readonly BlockCollection $blocks;

    public ?BaseComponent $parent = null;

    public function __construct(array $data)
    {
        $this->parent = $data['parent'] ?? null;
        $this->id = $data['id'] ?? '';
        $this->type = $data['type'] ?? $this->defaultType();
        $this->name = $data['name'] ?? '';
        $this->disabled = ! empty($data['disabled']);

        $this->settings = ($data['settings'] ?? null) instanceof Settings
            ? $data['settings']
            : new Settings($data['settings'] ?? []);

        $this->blocks = ($data['blocks'] ?? null) instanceof BlockCollection
            ? $data['blocks']
            : new BlockCollection;
    }

    /**
     * Render the HTML data-attributes required by the page builder editor.
     * Returns an empty string when not in editor mode.
     */
    abstract public function editorAttributes(): string;

    /**
     * Default type value when none is provided in the data array.
     */
    protected function defaultType(): string
    {
        return '';
    }

    /**
     * Return a new instance of the component with the given settings.
     */
    public function withSettings(Settings $settings): static
    {
        return new static([
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'disabled' => $this->disabled,
            'settings' => $settings,
            'blocks' => $this->blocks,
            'parent' => $this->parent,
        ]);
    }

    // ─── Serialization ──────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'disabled' => $this->disabled,
            'settings' => $this->settings->toArray(),
            'blocks' => $this->blocks->toArray(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        $data = $this->toArray();
        unset($data['parent']);

        return $data;
    }
}
