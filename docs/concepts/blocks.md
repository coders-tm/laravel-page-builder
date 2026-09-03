---
title: Blocks
---

# Blocks

Blocks are reusable components that live inside sections (or inside other blocks). They represent the building blocks of your page content.

## What is a Block?

A block is a standalone Blade component that includes both its visual template and its configuration schema.

Key characteristics:

- **Reusable**: The same block type can be used multiple times across sections
- **Nestable**: Container blocks can hold child blocks to any depth
- **Configurable**: Settings are defined via the `@schema` directive
- **Theme-aware**: Blocks can be global (theme) or local to a section

## Creating a Block

### 1. Create the Blade File

Place block templates in the configured blocks directory (default: `resources/views/blocks/`).

```blade
{{-- resources/views/blocks/text.blade.php --}}
@schema([
    'name' => 'Text',
    'settings' => [
        ['id' => 'content', 'type' => 'richtext', 'label' => 'Content', 'default' => ''],
        ['id' => 'alignment', 'type' => 'text_alignment', 'label' => 'Alignment', 'default' => 'left'],
    ],
])

<div {!! $block->editorAttributes() !!} class="text-{{ $block->settings->alignment }}">
    {!! $block->settings->content !!}
</div>
```

### 2. Understanding the `@schema()` Array

| Key        | Type   | Description                                               |
| ---------- | ------ | --------------------------------------------------------- |
| `name`     | string | **Required.** Human-readable name shown in the editor     |
| `settings` | array  | Setting definitions with `id`, `type`, `label`, `default` |

### 3. Block Template API

| Property / Method            | Description                                      |
| ---------------------------- | ------------------------------------------------ |
| `$block->id`                 | Unique block instance ID                         |
| `$block->type`               | Block type identifier (matches filename)         |
| `$block->settings->key`      | Typed setting access with defaults               |
| `$block->blocks`             | `BlockCollection` of nested child blocks         |
| `$block->editorAttributes()` | Editor `data-*` attributes                       |
| `$section`                   | Parent section (always available in block views) |
| `@blocks($block)`            | Renders child blocks of this container           |

## Block Types

### Theme Blocks

Theme blocks are registered globally and can be referenced by any section that declares `['type' => '@theme']` in its `blocks` array.

```blade
{{-- resources/views/blocks/row.blade.php --}}
@schema([
    'name' => 'Row',
    'settings' => [
        [
            'id' => 'columns',
            'type' => 'select',
            'label' => 'Columns',
            'default' => '2',
            'options' => [
                ['value' => '1', 'label' => '1 Column'],
                ['value' => '2', 'label' => '2 Columns'],
                ['value' => '3', 'label' => '3 Columns'],
            ],
        ],
    ],
    'blocks' => [
        ['type' => 'column'],
    ],
])

<div {!! $block->editorAttributes() !!}
    class="grid grid-cols-{{ $block->settings->columns }}">
    @blocks($block)
</div>
```

### Local Blocks (Inline Definitions)

Local blocks are defined directly inside a section's `@schema` `blocks` array. They are scoped to that section only and do not require a separate Blade file.

Detection rule: an entry is a **local block** if it has both `type` and `name` keys.

```blade
@schema([
    'name' => 'Slideshow',
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
```

Local blocks can also be **containers** with nested child blocks (also defined inline):

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
                        ['id' => 'icon', 'type' => 'icon_fa', 'label' => 'Icon'],
                        ['id' => 'label', 'type' => 'text', 'label' => 'Label'],
                        ['id' => 'value', 'type' => 'richtext', 'label' => 'Value'],
                    ],
                ],
            ],
        ],
    ],
])
```

Use local blocks when:

- A block is only meaningful within one specific section (e.g., a Slide only makes sense inside a Slideshow)
- You want to keep block definitions self-contained in a single file
- The block is simple and doesn't need to be reused across sections

### Container Blocks

Container blocks can hold child blocks:

```blade
@schema([
    'name' => 'Column',
    'blocks' => [
        ['type' => '@theme'],
    ],
])

<div {!! $block->editorAttributes() !!}>
    @blocks($block)
</div>
```

## Block Detection: Local vs Theme Reference

In `@schema` `blocks` arrays, entries are detected as either **local definitions** or **theme-block references**:

| Entry                                 | Type             | Detection                  | Resolution                         |
| ------------------------------------- | ---------------- | -------------------------- | ---------------------------------- |
| `['type' => 'x', 'name' => 'X', ...]` | Local definition | Has both `type` and `name` | Used as-is, scoped to this section |
| `['type' => 'x']`                     | Theme reference  | Only has `type` key        | Resolved from global BlockRegistry |
| `['type' => '@theme']`                | Wildcard         | Type is `@theme`           | Accepts any registered theme block |

Schema resolution order (in `Renderer`):

1. Check for an inline (local) block schema in the section
2. Fall back to the global BlockRegistry for theme references

## Block Registration

### Automatic Registration

Blocks are automatically discovered from the configured blocks directory:

```php
// config/pagebuilder.php
'blocks' => resource_path('views/blocks'),
```

### Manual Registration

Register additional directories:

```php
use PageBuilder\Facades\Block;

// In a service provider's boot() method
Block::add(resource_path('views/custom-blocks'));
```

### Programmatic Registration

Register a block without a Blade file:

```php
use PageBuilder\Facades\Block;
use PageBuilder\Schema\BlockSchema;

Block::register('custom-card', new BlockSchema([
    'name' => 'Custom Card',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title'],
    ],
]));
```

## Block Settings

Blocks use the same setting types as sections:

```blade
@schema([
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
        ['id' => 'icon', 'type' => 'icon_fa', 'label' => 'Icon', 'default' => 'fas fa-star'],
        ['id' => 'color', 'type' => 'color', 'label' => 'Color', 'default' => '#6366f1'],
        ['id' => 'show_badge', 'type' => 'checkbox', 'label' => 'Show Badge', 'default' => true],
    ],
])
```

## Accessing Parent Section

Blocks can access their parent section:

```blade
{{-- In a block Blade file --}}
<div class="block-wrapper">
    <p>Parent section: {{ $section->type }}</p>
    <p>Section title: {{ $section->settings->title }}</p>
</div>
```

## Container Blocks Example

Here's a complete example of a container block system:

```blade
{{-- resources/views/blocks/row.blade.php --}}
@schema([
    'name' => 'Row',
    'settings' => [
        [
            'id' => 'columns',
            'type' => 'select',
            'label' => 'Columns',
            'default' => '2',
            'options' => [
                ['value' => '1', 'label' => '1 Column'],
                ['value' => '2', 'label' => '2 Columns'],
                ['value' => '3', 'label' => '3 Columns'],
            ],
        ],
        [
            'id' => 'gap',
            'type' => 'select',
            'label' => 'Gap',
            'default' => 'md',
            'options' => [
                ['value' => 'none', 'label' => 'None'],
                ['value' => 'sm', 'label' => 'Small'],
                ['value' => 'md', 'label' => 'Medium'],
                ['value' => 'lg', 'label' => 'Large'],
            ],
        ],
    ],
    'blocks' => [
        ['type' => 'column'],
    ],
    'presets' => [
        [
            'name' => 'Two Columns',
            'settings' => ['columns' => '2'],
            'blocks' => [
                ['type' => 'column'],
                ['type' => 'column'],
            ],
        ],
        [
            'name' => 'Three Columns',
            'settings' => ['columns' => '3'],
            'blocks' => [
                ['type' => 'column'],
                ['type' => 'column'],
                ['type' => 'column'],
            ],
        ],
    ],
])

<div {!! $block->editorAttributes() !!}
    class="grid grid-cols-{{ $block->settings->columns }} gap-{{ $block->settings->gap }}">
    @blocks($block)
</div>
```

```blade
{{-- resources/views/blocks/column.blade.php --}}
@schema([
    'name' => 'Column',
    'settings' => [
        ['id' => 'padding', 'type' => 'select', 'label' => 'Padding', 'default' => 'none',
         'options' => [
             ['value' => 'none', 'label' => 'None'],
             ['value' => 'sm', 'label' => 'Small'],
             ['value' => 'md', 'label' => 'Medium'],
             ['value' => 'lg', 'label' => 'Large'],
         ]],
    ],
    'blocks' => [
        ['type' => '@theme'],
    ],
])

<div {!! $block->editorAttributes() !!} class="p-{{ $block->settings->padding }}">
    @blocks($block)
</div>
```

## Tips

1. **Keep blocks focused** — Each block should represent one UI component
2. **Use theme blocks** — Make blocks global for reuse across sections
3. **Set defaults** — Always provide sensible defaults for settings
4. **Use presets** — Provide common configurations as presets
5. **Test nesting** — Ensure container blocks work at different depths
