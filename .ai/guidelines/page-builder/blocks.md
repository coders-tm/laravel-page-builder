# Laravel Page Builder — Block System

---

## What Is a Block?

A **block** is a reusable content element that lives inside a section (or inside another block). Blocks are:

- A **Blade view** file at `resources/views/blocks/{type}.blade.php`
- Registered automatically by `BlockRegistry`
- Rendered with a `$block` runtime object and `$section` parent context
- Nestable to any depth (container blocks like `row` can hold `column` blocks)

---

## Block Blade File

### Simple (leaf) block — no children

```blade
{{-- resources/views/blocks/button.blade.php --}}

@schema([
    'name' => 'Button',
    'settings' => [
        ['id' => 'label',  'type' => 'text',   'label' => 'Label',  'default' => 'Click Me'],
        ['id' => 'url',    'type' => 'url',    'label' => 'URL',    'default' => '#'],
        ['id' => 'style',  'type' => 'select', 'label' => 'Style',  'default' => 'primary',
         'options' => [
             ['value' => 'primary',   'label' => 'Primary'],
             ['value' => 'secondary', 'label' => 'Secondary'],
             ['value' => 'outline',   'label' => 'Outline'],
         ]],
    ],
])

<a {!! $block->editorAttributes() !!}
   href="{{ $block->settings->url }}"
   class="btn btn-{{ $block->settings->style }}">
    {{ $block->settings->label }}
</a>
```

### Container block — accepts child blocks

```blade
{{-- resources/views/blocks/row.blade.php --}}

@schema([
    'name' => 'Row',
    'settings' => [
        ['id' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '2',
         'options' => [
             ['value' => '1', 'label' => '1 Column'],
             ['value' => '2', 'label' => '2 Columns'],
             ['value' => '3', 'label' => '3 Columns'],
             ['value' => '4', 'label' => '4 Columns'],
         ]],
        ['id' => 'gap', 'type' => 'select', 'label' => 'Gap', 'default' => 'md',
         'options' => [
             ['value' => 'none', 'label' => 'None'],
             ['value' => 'sm',   'label' => 'Small'],
             ['value' => 'md',   'label' => 'Medium'],
             ['value' => 'lg',   'label' => 'Large'],
         ]],
    ],
    'blocks' => [
        ['type' => 'column'],   // bare reference → resolved from BlockRegistry
    ],
    'presets' => [
        ['name' => 'Two Columns',
         'settings' => ['columns' => '2'],
         'blocks' => [['type' => 'column'], ['type' => 'column']]],
    ],
])

<div {!! $block->editorAttributes() !!} class="grid grid-cols-{{ $block->settings->columns }}">
    @blocks($block)    {{-- renders child column blocks --}}
</div>
```

---

## `@blocks()` — Dual Context

`@blocks()` accepts either a `Section` or a `Block`:

| Expression | Calls | Use case |
|---|---|---|
| `@blocks($section)` | `Renderer::renderBlocks(Section)` | Render all top-level blocks of a section |
| `@blocks($block)` | `Renderer::renderBlockChildren(Block, ?Section)` | Render nested children of a container block |

**Rule:** Never call the renderer directly. Always use `@blocks()`.

---

## Block Template API

Inside a block Blade view, `$block` and `$section` are always available:

```blade
$block->id                   {{-- unique instance ID --}}
$block->type                 {{-- block type (e.g. "row", "column") --}}
$block->settings->label      {{-- magic __get with default resolution --}}
$block->settings->get('label', 'fallback')  {{-- explicit fallback --}}
$block->settings->all()      {{-- all settings as key→value array --}}
$block->blocks               {{-- BlockCollection of child blocks --}}
$block->blocks->count()      {{-- number of children --}}
$block->blocks->ofType('column')  {{-- filter children by type --}}
$block->editorAttributes()   {{-- "data-block-id=..." or "" --}}

$section                     {{-- parent Section (may be null for isolated renders) --}}
$section->settings->title    {{-- access parent section settings --}}

@blocks($block)              {{-- renders all child blocks --}}
```

---

## BlockSchema PHP Class

The schema is parsed from `@schema()` and stored as a `BlockSchema` value object:

```php
use Coderstm\PageBuilder\Schema\BlockSchema;

$schema = BlockSchema::fromArray([
    'name'     => 'Row',
    'settings' => [...],
    'blocks'   => [...],
]);

$schema->type;                 // auto-set from Blade filename
$schema->name;                 // 'Row'
$schema->settings;             // SettingSchema[]
$schema->blocks;               // BlockSchema[] (local child definitions)
$schema->allowedBlockTypes;    // string[] (bare type references)
$schema->acceptsThemeBlocks(); // bool
$schema->settingDefaults();    // ['columns' => '2', 'gap' => 'md', ...]
```

---

## BlockRegistry

```php
use Coderstm\PageBuilder\Facades\Block;

// Get schema by type
$schema = Block::get('row');       // BlockSchema

// Get all theme blocks
$all = Block::all();               // BlockSchema[]

// Check if registered
$exists = Block::has('row');       // bool
```

---

## Block Nesting — How It Works

### Page JSON (nested example)

```json
{
    "blocks": {
        "row1": {
            "type": "row",
            "settings": { "columns": "3" },
            "blocks": {
                "col-a": { "type": "column", "settings": {}, "blocks": {}, "order": [] },
                "col-b": { "type": "column", "settings": {}, "blocks": {}, "order": [] },
                "col-c": { "type": "column", "settings": {}, "blocks": {}, "order": [] }
            },
            "order": ["col-a", "col-b", "col-c"]
        }
    },
    "order": ["row1"]
}
```

### Hydration is recursive

`Renderer::hydrateBlocks()` recursively processes each `blocks` map. Every `Block` object always has a `BlockCollection $blocks` property — leaf blocks have an empty one.

---

## Block Registration — Theme Blocks vs Local Blocks

### Theme block (global, registered via BlockRegistry)

- Lives at `resources/views/blocks/{type}.blade.php`
- Discoverable by any section as `{ type: 'row' }` (bare reference)
- Can be referenced with `['type' => '@theme']` to allow any theme block

### Local block (defined inline in a section's schema)

- Defined directly in a section's `@schema()` `blocks` array with extra keys
- Not stored in `BlockRegistry` — only available within that section
- No child-slot unless explicitly given a `blocks` key

```php
// Section schema — local block definition
'blocks' => [
    [
        'type'     => 'testimonial',
        'name'     => 'Testimonial',              // extra key → local block
        'settings' => [
            ['id' => 'quote',  'type' => 'textarea', 'label' => 'Quote',  'default' => ''],
            ['id' => 'author', 'type' => 'text',     'label' => 'Author', 'default' => ''],
        ],
        // No 'blocks' key → no child slot → no "Add block" appears in editor
    ],
]
```

**Detection rule:** Entry with ONLY `type` → theme reference → registry lookup. Entry with `name`, `settings`, or other keys → local definition → used as-is.

---

## Built-in Theme Blocks (Package)

### `row.blade.php` — Responsive Grid Row

| Setting | Type | Default | Notes |
|---|---|---|---|
| `columns` | select | `2` | 1–6 columns |
| `gap` | select | `md` | none/xs/sm/md/lg/xl |
| `vertical_alignment` | select | `start` | start/center/end/stretch |
| `reverse_on_mobile` | checkbox | `false` | Reverses column order on mobile |
| `full_width` | checkbox | `false` | Future full-bleed override |

Accepted child blocks: `column`

### `column.blade.php` — Flex Column

| Setting | Type | Default | Notes |
|---|---|---|---|
| `width` | select | `auto` | auto or col-span-1 to col-span-6 |
| `horizontal_alignment` | select | `start` | start/center/end |
| `vertical_alignment` | select | `start` | start/center/end/between |
| `padding` | select | `none` | none/sm/md/lg/xl |
| `background_color` | color | `''` | CSS background color |
| `background_image` | image_picker | `''` | Background image |
| `hide_on_mobile` | checkbox | `false` | Hidden below `sm` |
| `hide_on_desktop` | checkbox | `false` | Hidden above `sm` |

Accepted child blocks: `@theme` (any)

---

## Example: Card Block

```blade
{{-- resources/views/blocks/card.blade.php --}}

@schema([
    'name' => 'Card',
    'settings' => [
        ['id' => 'image',       'type' => 'image_picker', 'label' => 'Image',       'default' => ''],
        ['id' => 'title',       'type' => 'text',         'label' => 'Title',       'default' => 'Card Title'],
        ['id' => 'description', 'type' => 'textarea',     'label' => 'Description', 'default' => ''],
        ['id' => 'link',        'type' => 'url',          'label' => 'Link',        'default' => ''],
        ['id' => 'rounded',     'type' => 'checkbox',     'label' => 'Rounded',     'default' => true],
    ],
])

<div {!! $block->editorAttributes() !!}
     class="bg-white shadow {{ $block->settings->rounded ? 'rounded-xl' : '' }} overflow-hidden">
    @if($block->settings->image)
        <img src="{{ $block->settings->image }}" alt="{{ $block->settings->title }}" class="w-full h-48 object-cover">
    @endif
    <div class="p-6">
        <h3 class="text-xl font-semibold mb-2">{{ $block->settings->title }}</h3>
        <p class="text-gray-600">{{ $block->settings->description }}</p>
        @if($block->settings->link)
            <a href="{{ $block->settings->link }}" class="mt-4 inline-block text-blue-600 hover:underline">
                Learn More →
            </a>
        @endif
    </div>
</div>
```

---

## Example: Accordion Container Block

```blade
{{-- resources/views/blocks/accordion.blade.php --}}

@schema([
    'name' => 'Accordion',
    'settings' => [
        ['id' => 'allow_multiple', 'type' => 'checkbox', 'label' => 'Allow Multiple Open', 'default' => false],
    ],
    'blocks' => [
        [
            'type'     => 'accordion-item',
            'name'     => 'Accordion Item',
            'settings' => [
                ['id' => 'question', 'type' => 'text',     'label' => 'Question', 'default' => ''],
                ['id' => 'answer',   'type' => 'richtext', 'label' => 'Answer',   'default' => ''],
            ],
        ],
    ],
    'max_blocks' => 20,
])

<div {!! $block->editorAttributes() !!} class="space-y-2">
    @blocks($block)
</div>
```

---

## Disabled Blocks

Setting `disabled: true` in page JSON silently excludes a block from hydration and rendering. Disabled blocks never surface in `BlockCollection`. This is handled automatically by `Renderer::hydrateBlocks()`.
