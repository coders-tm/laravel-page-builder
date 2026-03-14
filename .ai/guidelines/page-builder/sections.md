# Laravel Page Builder — Section System

---

## What Is a Section?

A **section** is the primary structural unit of a page. It is:

- A **Blade view** file that defines its own schema via `@schema()`
- Registered automatically when discovered by `SectionRegistry`
- Identified by the Blade filename (without `.blade.php`)
- Rendered with a **runtime `Section` object** injected as `$section`

---

## Section Blade File

Sections live at `resources/views/sections/{type}.blade.php` (configurable via `config('pagebuilder.sections')`).

### Minimal section

```blade
{{-- resources/views/sections/hero.blade.php --}}

@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title',    'type' => 'text', 'label' => 'Title',    'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'text', 'label' => 'Subtitle', 'default' => ''],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    <p>{{ $section->settings->subtitle }}</p>
</section>
```

### Section with blocks

```blade
{{-- resources/views/sections/section.blade.php --}}

@schema([
    'name' => 'Section',
    'settings' => [
        ['id' => 'max_width',   'type' => 'select', 'label' => 'Max Width',
         'default' => '7xl',
         'options' => [
             ['value' => 'full', 'label' => 'Full'],
             ['value' => '7xl',  'label' => '7xl'],
         ]],
        ['id' => 'padding_top',    'type' => 'select', 'label' => 'Padding Top',    'default' => 'md'],
        ['id' => 'padding_bottom', 'type' => 'select', 'label' => 'Padding Bottom', 'default' => 'md'],
        ['id' => 'background_color', 'type' => 'color', 'label' => 'Background', 'default' => ''],
    ],
    'blocks' => [
        ['type' => 'row'],      // bare reference → resolved from theme block registry
        ['type' => '@theme'],   // accepts any registered theme block
    ],
    'presets' => [
        ['name' => 'Section'],
    ],
])

<section {!! $section->editorAttributes() !!}
    @if($section->settings->background_color)
        style="background-color: {{ $section->settings->background_color }}"
    @endif
>
    <div class="mx-auto max-w-{{ $section->settings->max_width }}">
        @blocks($section)
    </div>
</section>
```

---

## `@schema()` Directive — Section Fields

The `@schema()` array is extracted at **registration time** by `SchemaExtractor` and is a **no-op at render time**.

### Full schema structure

```php
@schema([
    'name'      => 'Hero',           // Display name in editor
    'tag'       => 'section',        // HTML tag hint (optional)
    'settings'  => [...],            // Array of SettingSchema arrays
    'blocks'    => [...],            // Allowed block definitions
    'max_blocks' => 10,              // Maximum number of blocks (optional)
    'presets'   => [...],            // Quick-add presets (optional)
    'limit'     => 1,                // Max instances on a page (optional)
])
```

---

## Setting Types

| type | Description | Extra fields |
|---|---|---|
| `text` | Single-line text input | — |
| `textarea` | Multi-line text | — |
| `richtext` | TipTap rich-text editor | — |
| `number` | Numeric input | `min`, `max`, `step` |
| `range` | Slider | `min`, `max`, `step` |
| `select` | Dropdown | `options: [{value, label}]` |
| `radio` | Radio group | `options: [{value, label}]` |
| `checkbox` | Boolean toggle | — |
| `color` | Color picker | — |
| `image_picker` | Media library image | — |
| `alignment` | Alignment picker | — |
| `icon_picker` | Icon selector | — |
| `url` | URL input | — |

### Setting definition shape

```php
[
    'id'      => 'title',        // Required. Key used in $section->settings->title
    'type'    => 'text',         // Required. Setting input type
    'label'   => 'Title',        // Required. Editor label
    'default' => 'Welcome',      // Optional. Fallback when not set
    'options' => [               // Required for select/radio
        ['value' => 'sm', 'label' => 'Small'],
        ['value' => 'lg', 'label' => 'Large'],
    ],
    'min'     => 0,              // For number/range
    'max'     => 100,            // For number/range
    'step'    => 1,              // For number/range
]
```

---

## Blocks Array — Local vs Theme Reference

The `blocks` array in `@schema()` can contain two kinds of entries:

### Bare theme reference (lookup from registry)

```php
'blocks' => [
    ['type' => 'row'],      // Only type — resolved from BlockRegistry
    ['type' => 'card'],     // Only type — resolved from BlockRegistry
    ['type' => '@theme'],   // Wildcard — accepts ANY registered theme block
]
```

### Local definition (self-contained, no registry lookup)

```php
'blocks' => [
    [
        'type'     => 'feature',
        'name'     => 'Feature Item',    // Extra keys → local definition
        'settings' => [
            ['id' => 'icon',  'type' => 'icon_picker', 'label' => 'Icon',  'default' => ''],
            ['id' => 'title', 'type' => 'text',        'label' => 'Title', 'default' => ''],
        ],
    ],
]
```

**Detection rule:** An entry with only `type` is a bare reference. Any extra key (`name`, `settings`, `blocks`, etc.) makes it a local definition.

---

## Section Template API

Inside a section Blade view, `$section` is a `Section` runtime object:

```blade
$section->id                  {{-- unique instance ID (e.g. "hero") --}}
$section->type                {{-- schema type (e.g. "hero") --}}
$section->settings->title     {{-- magic __get with default resolution --}}
$section->settings->get('title', 'fallback')  {{-- explicit fallback --}}
$section->settings->all()     {{-- all settings as key→value array --}}
$section->blocks              {{-- BlockCollection of top-level blocks --}}
$section->blocks->count()     {{-- number of blocks --}}
$section->editorAttributes()  {{-- "data-editor-section data-section-id=..." or "" --}}

@blocks($section)             {{-- renders all top-level blocks --}}
```

---

## Presets

Presets let users add a section pre-configured with settings and blocks:

```php
'presets' => [
    [
        'name'     => 'Hero with Image',
        'settings' => [
            'title'            => 'Welcome',
            'background_image' => '',
        ],
        'blocks' => [
            ['type' => 'row', 'settings' => ['columns' => '2']],
        ],
    ],
]
```

---

## SectionSchema PHP Class

```php
// src/Schema/SectionSchema.php
use Coderstm\PageBuilder\Schema\SectionSchema;

$schema = SectionSchema::fromArray([
    'name'     => 'Hero',
    'settings' => [...],
    'blocks'   => [...],
]);

$schema->name;                // 'Hero'
$schema->settings;            // SettingSchema[]
$schema->blocks;              // BlockSchema[] (local definitions)
$schema->allowedBlockTypes;   // string[] (bare type references)
$schema->acceptsThemeBlocks(); // bool — true if '@theme' is in blocks
$schema->settingDefaults();    // ['title' => 'Welcome', ...]
$schema->blockSchema('row');   // BlockSchema|null for local definition
```

---

## SectionRegistry

```php
use Coderstm\PageBuilder\Facades\Section;

// Get schema by type
$schema = Section::get('hero');        // SectionSchema

// Get all registered schemas
$all = Section::all();                 // SectionSchema[]

// Check if registered
$exists = Section::has('hero');        // bool
```

---

## Example: Feature List Section

```blade
{{-- resources/views/sections/features.blade.php --}}

@schema([
    'name' => 'Feature List',
    'settings' => [
        ['id' => 'heading',   'type' => 'text',   'label' => 'Heading',   'default' => 'Features'],
        ['id' => 'columns',   'type' => 'select', 'label' => 'Columns',   'default' => '3',
         'options' => [
             ['value' => '2', 'label' => '2 Columns'],
             ['value' => '3', 'label' => '3 Columns'],
             ['value' => '4', 'label' => '4 Columns'],
         ]],
    ],
    'blocks' => [
        [
            'type'     => 'feature-item',
            'name'     => 'Feature Item',
            'settings' => [
                ['id' => 'icon',        'type' => 'icon_picker', 'label' => 'Icon',        'default' => ''],
                ['id' => 'title',       'type' => 'text',        'label' => 'Title',       'default' => 'Feature'],
                ['id' => 'description', 'type' => 'textarea',    'label' => 'Description', 'default' => ''],
            ],
        ],
    ],
    'max_blocks' => 12,
    'presets' => [
        ['name' => 'Three Features'],
    ],
])

<section {!! $section->editorAttributes() !!} class="py-16">
    <div class="container mx-auto">
        <h2 class="text-3xl font-bold text-center mb-10">
            {{ $section->settings->heading }}
        </h2>
        <div class="grid grid-cols-{{ $section->settings->columns }} gap-8">
            @blocks($section)
        </div>
    </div>
</section>
```

---

## Built-in Sections (Package)

The package ships with one built-in section at `resources/views/sections/section.blade.php`:

| Setting | Type | Default | Notes |
|---|---|---|---|
| `anchor` | text | `''` | HTML `id` attribute |
| `padding_top` | select | `md` | none/xs/sm/md/lg/xl/2xl |
| `padding_bottom` | select | `md` | none/xs/sm/md/lg/xl/2xl |
| `max_width` | select | `7xl` | full/sm/md/lg/xl/2xl/5xl/6xl/7xl |
| `background_color` | color | `''` | CSS background color |
| `background_image` | image_picker | `''` | Background image URL |
| `background_overlay_opacity` | range | `0` | 0–100 |
| `color_scheme` | select | `default` | default/light/dark/primary/accent |

Accepted blocks: `row`, `@theme`
