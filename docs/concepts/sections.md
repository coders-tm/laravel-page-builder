---
title: Sections
---

# Sections

Sections are the top-level building blocks of a page. They represent horizontal slices of content that can be added, removed, and reordered.

## What is a Section?

A section is a standalone Blade component that includes both its visual template and its configuration schema.

Key characteristics:

- **Self-contained**: Each section lives in its own `.blade.php` file
- **Configurable**: Settings are defined via the `@schema` directive
- **Nestable**: Sections can contain child blocks
- **Reusable**: The same section type can be used multiple times on a single page

## Creating a Section

### 1. Create the Blade File

Place section templates in the configured sections directory (default: `resources/views/sections/`).

```blade
{{-- resources/views/sections/hero.blade.php --}}
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title',    'type' => 'text',  'label' => 'Title',    'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'text',  'label' => 'Subtitle', 'default' => ''],
        ['id' => 'bg_color', 'type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
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
])

<section {!! $section->editorAttributes() !!}
    style="background-color: {{ $section->settings->bg_color }}">
    <div class="container mx-auto px-4">
        <h1>{{ $section->settings->title }}</h1>
        <p>{{ $section->settings->subtitle }}</p>
        @blocks($section)
    </div>
</section>
```

### 2. Understanding the `@schema()` Array

| Key          | Type   | Description                                                  |
| ------------ | ------ | ------------------------------------------------------------ |
| `name`       | string | **Required.** Human-readable name shown in the editor        |
| `settings`   | array  | Setting definitions with `id`, `type`, `label`, `default`    |
| `blocks`     | array  | Allowed child block types (inline definitions or theme refs) |
| `presets`    | array  | Pre-configured templates shown in the "Add section" picker   |
| `max_blocks` | int    | Maximum number of child blocks allowed                       |

### 3. Section Template API

| Property / Method              | Description                                                |
| ------------------------------ | ---------------------------------------------------------- |
| `$section->id`                 | Unique instance ID                                         |
| `$section->type`               | Section type identifier (matches filename)                 |
| `$section->name`               | Human-readable name from schema                            |
| `$section->settings->key`      | Typed setting access with automatic defaults               |
| `$section->blocks`             | `BlockCollection` of hydrated top-level blocks             |
| `$section->editorAttributes()` | Editor `data-*` attributes (empty string when not editing) |
| `@blocks($section)`            | Renders all top-level blocks                               |

## Section Registration

### Automatic Registration

Sections are automatically discovered from the configured sections directory:

```php
// config/pagebuilder.php
'sections' => resource_path('views/sections'),
```

### Manual Registration

Register additional directories:

```php
use PageBuilder\Facades\Section;

// In a service provider's boot() method
Section::add(resource_path('views/custom-sections'));
```

### Programmatic Registration

Register a section without a Blade file:

```php
use PageBuilder\Facades\Section;
use PageBuilder\Schema\SectionSchema;

Section::register('custom-hero', new SectionSchema([
    'name' => 'Custom Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Hello'],
    ],
]), 'my-views::sections.custom-hero');
```

## Section Settings

### Setting Types

| Type               | Description                      | Extra Keys                  |
| ------------------ | -------------------------------- | --------------------------- |
| `text`             | Single-line text input           | —                           |
| `textarea`         | Multi-line text input            | —                           |
| `richtext`         | Rich text editor (multi-line)    | —                           |
| `inline_richtext`  | Rich text editor (single-line)   | —                           |
| `select`           | Dropdown select                  | `options: [{value, label}]` |
| `radio`            | Radio buttons                    | `options: [{value, label}]` |
| `checkbox`         | Boolean toggle                   | —                           |
| `range`            | Numeric slider                   | `min`, `max`, `step`        |
| `number`           | Number input                     | `min`, `max`, `step`        |
| `color`            | Color picker (hex)               | —                           |
| `color_background` | CSS background (gradients)       | —                           |
| `image_picker`     | Media library selector           | —                           |
| `url`              | Link/URL input                   | —                           |
| `icon_fa`          | FontAwesome icon picker          | —                           |
| `icon_md`          | Material Design icon picker      | —                           |
| `text_alignment`   | Left/Center/Right segmented ctrl | —                           |
| `html`             | Raw HTML code editor             | —                           |
| `blade`            | Blade template code editor       | —                           |
| `header`           | Sidebar section divider          | `content`                   |
| `paragraph`        | Sidebar informational text       | `content`                   |
| `external`         | Dynamic API-driven selector      | —                           |

### Accessing Settings in Blade

```blade
{{-- Text settings --}}
<h1>{{ $section->settings->title }}</h1>

{{-- Color settings --}}
<div style="background-color: {{ $section->settings->bg_color }}">

{{-- Checkbox settings --}}
@if($section->settings->show_subtitle)
    <p>{{ $section->settings->subtitle }}</p>
@endif

{{-- Select/Radio settings --}}
<div class="text-{{ $section->settings->alignment }}">

{{-- Image settings --}}
<img src="{{ $section->settings->image }}" alt="Hero image">
```

## Block Definitions

Sections can define **local blocks** (inline, section-scoped) or reference **theme blocks** (global, reusable across sections).

### Local Blocks (Inline Definitions)

Local blocks are defined directly inside a section's `@schema` `blocks` array. They are scoped to that section only — no separate Blade file needed.

```blade
@schema([
    'name' => 'Slideshow',
    'tag' => 'section',
    'class' => 'slideshow',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Slideshow', 'default' => ''],
    ],
    'blocks' => [
        [
            'type' => 'slide',
            'name' => 'Slide',
            'settings' => [
                ['id' => 'image', 'type' => 'image_picker', 'label' => 'Image', 'default' => ''],
                ['id' => 'title', 'type' => 'text', 'label' => 'Slide Title', 'default' => ''],
                ['id' => 'link', 'type' => 'url', 'label' => 'Link', 'default' => '#'],
            ],
        ],
    ],
])

<section {!! $section->editorAttributes() !!} class="slideshow">
    <h2>{{ $section->settings->title }}</h2>
    <div class="slideshow-track">
        @foreach ($section->blocks as $block)
            <div {!! $block->editorAttributes() !!} class="slide">
                @if ($block->settings->image)
                    <img src="{{ $block->settings->image }}" alt="{{ $block->settings->title }}">
                @endif
                <h3>{{ $block->settings->title }}</h3>
            </div>
        @endforeach
    </div>
</section>
```

Detection rule: an entry is a **local block** if it has both `type` and `name` keys.

### Nested Local Blocks

Local blocks can also be **containers** with their own nested child blocks:

```blade
@schema([
    'name' => 'Contact Form',
    'blocks' => [
        [
            'type' => 'contact-info',
            'name' => 'Contact Info',
            'blocks' => [
                [
                    'type' => 'item',
                    'name' => 'Item',
                    'settings' => [
                        ['id' => 'icon', 'type' => 'icon_fa', 'label' => 'Icon', 'default' => 'fas fa-circle-info'],
                        ['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => ''],
                        ['id' => 'value', 'type' => 'richtext', 'label' => 'Value', 'default' => ''],
                    ],
                ],
            ],
        ],
    ],
])
```

Rendering nested local blocks:

```blade
@foreach ($section->blocks as $block)
    @if ($block->type === 'contact-info')
        <div {!! $block->editorAttributes() !!}>
            @foreach ($block->blocks as $item)
                <div {!! $item->editorAttributes() !!}>
                    <i class="{{ $item->settings->icon }}"></i>
                    <span>{{ $item->settings->label }}</span>
                    <p>{!! $item->settings->value !!}</p>
                </div>
            @endforeach
        </div>
    @endif
@endforeach
```

### Theme Block References

An entry with only a `type` key is a reference to a globally registered theme block:

```blade
@schema([
    'blocks' => [
        ['type' => 'row'],       {{-- references themes/blocks/row.blade.php --}}
        ['type' => 'column'],    {{-- references themes/blocks/column.blade.php --}}
    ],
])
```

### Wildcard (`@theme`)

The `@theme` wildcard allows any registered theme block to be added:

```blade
@schema([
    'blocks' => [
        ['type' => '@theme'],
    ],
])
```

### Detection Summary

| Entry                                 | Type             | How it's resolved                  |
| ------------------------------------- | ---------------- | ---------------------------------- |
| `['type' => 'x', 'name' => 'X', ...]` | Local definition | Used as-is, scoped to this section |
| `['type' => 'x']`                     | Theme reference  | Resolved from global BlockRegistry |
| `['type' => '@theme']`                | Wildcard         | Accepts any registered theme block |

## Presets

Presets are pre-configured templates shown in the "Add section" picker:

```blade
@schema([
    'presets' => [
        [
            'name' => 'Hero',
            // Default settings, no blocks
        ],
        [
            'name' => 'Hero with Row',
            'settings' => ['title' => 'Welcome'],
            'blocks' => [
                ['type' => 'row', 'settings' => ['columns' => '2']],
            ],
        ],
    ],
])
```

## Editor Attributes

The `editorAttributes()` method generates data attributes for the visual editor:

```blade
<section {!! $section->editorAttributes() !!}>
    {{-- Renders: data-section-id="abc123" data-section-type="hero" --}}
</section>
```

These attributes are only rendered when the editor is active.

## Tips

1. **Keep sections focused** — Each section should represent one content type
2. **Use presets** — Provide common configurations as presets
3. **Set defaults** — Always provide sensible defaults for settings
4. **Use blocks wisely** — Allow blocks only where nesting is needed
5. **Name clearly** — Use descriptive names for sections and settings
