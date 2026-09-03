---
title: Schema Reference
---

# Schema Reference

The `@schema` directive defines the structure of sections and blocks. It declares settings, child blocks, and presets.

## Syntax

```blade
@schema([
    'name' => 'Section Name',
    'settings' => [...],
    'blocks' => [...],
    'presets' => [...],
    'max_blocks' => 10,
])
```

## Schema Attributes

### name

- **Type:** `string`
- **Required:** Yes

Human-readable name shown in the editor.

```blade
@schema([
    'name' => 'Hero Section',
])
```

### settings

- **Type:** `array`
- **Required:** No

Array of setting definitions.

```blade
@schema([
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Hello'],
        ['id' => 'color', 'type' => 'color', 'label' => 'Color', 'default' => '#6366f1'],
    ],
])
```

### blocks

- **Type:** `array`
- **Required:** No

Array of allowed child block types.

```blade
@schema([
    'blocks' => [
        ['type' => 'row'],
        ['type' => 'text'],
        ['type' => '@theme'],
    ],
])
```

### presets

- **Type:** `array`
- **Required:** No

Array of pre-configured templates.

```blade
@schema([
    'presets' => [
        ['name' => 'Default'],
        ['name' => 'With Title', 'settings' => ['title' => 'Welcome']],
    ],
])
```

### max_blocks

- **Type:** `int`
- **Required:** No

Maximum number of child blocks allowed.

```blade
@schema([
    'max_blocks' => 5,
])
```

## Setting Definition

Each setting in the `settings` array is an associative array:

### Standard Attributes

| Attribute     | Type   | Required | Description                           |
| ------------- | ------ | -------- | ------------------------------------- |
| `type`        | string | Yes      | The input type                        |
| `id`          | string | Yes      | Unique identifier for the setting     |
| `label`       | string | No       | Display label shown in the editor     |
| `default`     | mixed  | No       | Default value                         |
| `info`        | string | No       | Helper text displayed below the field |
| `placeholder` | string | No       | Placeholder text                      |

### Type-Specific Attributes

| Type              | Extra Keys                   |
| ----------------- | ---------------------------- |
| `select`, `radio` | `options: [{value, label}]`  |
| `range`, `number` | `min`, `max`, `step`, `unit` |
| `header`          | `content`                    |
| `paragraph`       | `content`                    |

## Block Definition

Each block in the `blocks` array can be:

### Theme Reference

```blade
@schema([
    'blocks' => [
        ['type' => 'row'],
    ],
])
```

### Wildcard

```blade
@schema([
    'blocks' => [
        ['type' => '@theme'],
    ],
])
```

### Local Definition

```blade
@schema([
    'blocks' => [
        [
            'type' => 'custom-block',
            'name' => 'Custom Block',
            'settings' => [
                ['id' => 'content', 'type' => 'text', 'label' => 'Content'],
            ],
        ],
    ],
])
```

## Preset Definition

```blade
@schema([
    'presets' => [
        [
            'name' => 'Default',
            // Uses schema defaults
        ],
        [
            'name' => 'Custom',
            'settings' => ['title' => 'Custom Title'],
            'blocks' => [
                ['type' => 'row', 'settings' => ['columns' => '2']],
            ],
        ],
    ],
])
```

## Complete Example

```blade
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome', 'placeholder' => 'Enter title...'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => '', 'info' => 'Optional subtitle text'],
        ['id' => 'bg_color', 'type' => 'color', 'label' => 'Background Color', 'default' => '#6366f1'],
        ['id' => 'show_badge', 'type' => 'checkbox', 'label' => 'Show Badge', 'default' => true],
        ['id' => 'badge_text', 'type' => 'text', 'label' => 'Badge Text', 'default' => 'New', 'placeholder' => 'Badge text...'],
    ],
    'blocks' => [
        ['type' => 'row'],
        ['type' => '@theme'],
    ],
    'presets' => [
        ['name' => 'Hero'],
        ['name' => 'Hero with Row', 'blocks' => [
            ['type' => 'row', 'settings' => ['columns' => '2']],
        ]],
    ],
    'max_blocks' => 3,
])
```

## Blade Template Usage

```blade
<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>

    @if($section->settings->subtitle)
        <p>{{ $section->settings->subtitle }}</p>
    @endif

    <div style="background-color: {{ $section->settings->bg_color }}">
        @if($section->settings->show_badge)
            <span class="badge">{{ $section->settings->badge_text }}</span>
        @endif
    </div>

    @blocks($section)
</section>
```

## Tips

1. **Use descriptive names** — Make section/block names clear in the editor
2. **Set sensible defaults** — Always provide default values
3. **Use info text** — Help users understand what each setting does
4. **Limit blocks** — Use `max_blocks` to prevent excessive nesting
5. **Create presets** — Provide common configurations as presets
