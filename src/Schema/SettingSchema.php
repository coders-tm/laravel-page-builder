<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageBuilder\Schema;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Defines a single setting within a section or block schema.
 *
 * Each setting has a type (text, image, checkbox, etc.), an id that
 * becomes the key in the settings bag, a label, and an optional default.
 */
class SettingSchema implements Arrayable, JsonSerializable
{
    public readonly ?string $id;

    public readonly string $type;

    public readonly ?string $label;

    public readonly mixed $default;

    public readonly ?string $info;

    public readonly ?string $placeholder;

    public readonly ?string $content;

    /** @var array<int, array{label: string, value: mixed}>|null */
    public readonly ?array $options;

    public readonly ?int $min;

    public readonly ?int $max;

    public readonly ?int $step;

    public readonly ?string $unit;

    public function __construct(array $schema)
    {
        $this->id = $schema['id'] ?? null;
        $this->type = $schema['type'] ?? 'text';
        $this->label = $schema['label'] ?? null;
        $this->default = $schema['default'] ?? null;
        $this->info = $schema['info'] ?? null;
        $this->placeholder = $schema['placeholder'] ?? null;
        $this->content = $schema['content'] ?? null;
        $this->options = isset($schema['options']) ? $schema['options'] : null;
        $this->min = isset($schema['min']) ? (int) $schema['min'] : null;
        $this->max = isset($schema['max']) ? (int) $schema['max'] : null;
        $this->step = isset($schema['step']) ? (int) $schema['step'] : null;
        $this->unit = $schema['unit'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'default' => $this->default,
        ];

        if ($this->info !== null) {
            $data['info'] = $this->info;
        }
        if ($this->placeholder !== null) {
            $data['placeholder'] = $this->placeholder;
        }
        if ($this->content !== null) {
            $data['content'] = $this->content;
        }
        if ($this->options !== null) {
            $data['options'] = $this->options;
        }
        if ($this->min !== null) {
            $data['min'] = $this->min;
        }
        if ($this->max !== null) {
            $data['max'] = $this->max;
        }
        if ($this->step !== null) {
            $data['step'] = $this->step;
        }
        if ($this->unit !== null) {
            $data['unit'] = $this->unit;
        }

        return array_filter($data, fn ($v) => $v !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
