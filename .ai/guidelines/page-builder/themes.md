# Laravel Page Builder — Theme Development

---

## What Is a Theme?

A theme is a collection of **sections**, **blocks**, **layouts**, and **assets** that define the visual and structural identity of a site. Themes follow `qirolab/laravel-themer` conventions and are discovered automatically by the Page Builder.

---

## Theme Directory Structure

```
themes/
└── my-theme/
    ├── views/
    │   ├── layouts/
    │   │   └── page.blade.php          # Master layout (header, main, footer)
    │   ├── sections/                  # Theme sections (override or extend built-ins)
    │   │   ├── hero.blade.php
    │   │   ├── features.blade.php
    │   │   ├── cta.blade.php
    │   │   ├── testimonials.blade.php
    │   │   └── site-header.blade.php
    │   ├── blocks/                    # Theme-level reusable blocks
    │   │   ├── row.blade.php          # Can override built-in row
    │   │   ├── column.blade.php       # Can override built-in column
    │   │   ├── card.blade.php
    │   │   └── button.blade.php
    │   └── pages/                     # Page JSON files
    │       ├── home.json
    │       └── about.json
    └── assets/
        ├── css/
        │   └── theme.css
        └── js/
            └── theme.js
```

---

## Theme Registration

Themes are registered in `config/themer.php` (from `qirolab/laravel-themer`):

```php
return [
    'active_theme' => env('APP_THEME', 'default'),
    'themes_path'  => resource_path('themes'),
];
```

The Page Builder reads the active theme from this config and registers its sections and blocks on top of the built-in ones.

**Last registration wins** — theme sections/blocks shadow built-in ones with the same filename/type.

---

## Master Layout (`layouts/page.blade.php`)

The master layout integrates layout sections (header/footer) and yields the page content area:

```blade
{{-- themes/my-theme/views/layouts/page.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @pbEditorClass>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->meta_title ?? $page->title ?? config('app.name') }}</title>

    {{-- Theme assets --}}
    {{ theme_vite(['css/theme.css', 'js/theme.js']) }}

    {{-- Editor scripts (injected automatically in editor mode) --}}
    @pbEditorScripts
</head>
<body>

    {{-- Layout section: header --}}
    @sections('header')

    {{-- Page sections rendered here --}}
    @yield('content')

    {{-- Layout section: footer --}}
    @sections('footer')

</body>
</html>
```

### Key directives

| Directive | Purpose |
|---|---|
| `@pbEditorClass` | Adds `js pb-design-mode` class to `<html>` in editor mode |
| `@pbEditorScripts` | Injects editor interaction scripts in editor mode |
| `@sections('header')` | Renders the `header` layout section |
| `@sections('footer')` | Renders the `footer` layout section |
| `@yield('content')` | Where the page's sections are output |

---

## Theme Sections

Theme sections live at `themes/{name}/views/sections/` and follow the same rules as [sections](sections.md).

### Example: Site Header Section

```blade
{{-- themes/my-theme/views/sections/site-header.blade.php --}}

@schema([
    'name' => 'Site Header',
    'settings' => [
        ['id' => 'logo',   'type' => 'image_picker', 'label' => 'Logo',   'default' => ''],
        ['id' => 'sticky', 'type' => 'checkbox',     'label' => 'Sticky', 'default' => false],
    ],
    'blocks' => [
        [
            'type'     => 'nav-item',
            'name'     => 'Navigation Item',
            'settings' => [
                ['id' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Home'],
                ['id' => 'url',   'type' => 'url',  'label' => 'URL',   'default' => '/'],
            ],
        ],
    ],
])

<header {!! $section->editorAttributes() !!}
        class="{{ $section->settings->sticky ? 'sticky top-0 z-50' : '' }} bg-white shadow">
    <div class="container mx-auto flex items-center justify-between py-4">
        @if($section->settings->logo)
            <img src="{{ $section->settings->logo }}" alt="Logo" class="h-8">
        @else
            <span class="text-xl font-bold">{{ config('app.name') }}</span>
        @endif

        <nav class="flex gap-6">
            @blocks($section)
        </nav>
    </div>
</header>
```

### Example: CTA Section

```blade
{{-- themes/my-theme/views/sections/cta.blade.php --}}

@schema([
    'name' => 'Call to Action',
    'settings' => [
        ['id' => 'title',       'type' => 'text',   'label' => 'Title',       'default' => 'Ready to Get Started?'],
        ['id' => 'description', 'type' => 'textarea','label' => 'Description', 'default' => ''],
        ['id' => 'button_text', 'type' => 'text',   'label' => 'Button Text', 'default' => 'Get Started'],
        ['id' => 'button_url',  'type' => 'url',    'label' => 'Button URL',  'default' => '#'],
        ['id' => 'bg_color',    'type' => 'color',  'label' => 'Background',  'default' => '#1d4ed8'],
        ['id' => 'text_color',  'type' => 'color',  'label' => 'Text Color',  'default' => '#ffffff'],
    ],
    'presets' => [
        ['name' => 'CTA Banner'],
    ],
])

<section {!! $section->editorAttributes() !!}
    style="background-color: {{ $section->settings->bg_color }}; color: {{ $section->settings->text_color }}">
    <div class="container mx-auto text-center py-20">
        <h2 class="text-4xl font-bold mb-4">{{ $section->settings->title }}</h2>
        @if($section->settings->description)
            <p class="text-lg mb-8">{{ $section->settings->description }}</p>
        @endif
        <a href="{{ $section->settings->button_url }}"
           class="inline-block bg-white font-semibold py-3 px-8 rounded-lg"
           style="color: {{ $section->settings->bg_color }}">
            {{ $section->settings->button_text }}
        </a>
    </div>
</section>
```

---

## Theme Blocks

Theme blocks live at `themes/{name}/views/blocks/` and are auto-registered as **theme blocks** (reusable across any section that accepts `['type' => '@theme']` or a bare reference).

### Example: Image + Text Block

```blade
{{-- themes/my-theme/views/blocks/image-text.blade.php --}}

@schema([
    'name' => 'Image + Text',
    'settings' => [
        ['id' => 'image',      'type' => 'image_picker', 'label' => 'Image',      'default' => ''],
        ['id' => 'heading',    'type' => 'text',         'label' => 'Heading',    'default' => ''],
        ['id' => 'body',       'type' => 'richtext',     'label' => 'Body',       'default' => ''],
        ['id' => 'image_side', 'type' => 'select',       'label' => 'Image Side', 'default' => 'left',
         'options' => [
             ['value' => 'left',  'label' => 'Left'],
             ['value' => 'right', 'label' => 'Right'],
         ]],
    ],
])

<div {!! $block->editorAttributes() !!}
     class="flex {{ $block->settings->image_side === 'right' ? 'flex-row-reverse' : 'flex-row' }} gap-12 items-center">
    @if($block->settings->image)
        <div class="flex-1">
            <img src="{{ $block->settings->image }}" alt="{{ $block->settings->heading }}" class="w-full rounded-lg">
        </div>
    @endif
    <div class="flex-1">
        <h3 class="text-2xl font-bold mb-4">{{ $block->settings->heading }}</h3>
        <div class="prose">{!! $block->settings->body !!}</div>
    </div>
</div>
```

---

## Theme Settings (Global)

Theme settings are global settings that apply across all pages. They are configured in `config/pagebuilder.php`:

```php
'theme_settings_schema' => [
    [
        'id'      => 'primary_color',
        'type'    => 'color',
        'label'   => 'Primary Color',
        'default' => '#1d4ed8',
    ],
    [
        'id'      => 'font_family',
        'type'    => 'select',
        'label'   => 'Font Family',
        'default' => 'sans',
        'options' => [
            ['value' => 'sans',  'label' => 'Sans Serif'],
            ['value' => 'serif', 'label' => 'Serif'],
            ['value' => 'mono',  'label' => 'Monospace'],
        ],
    ],
],
'theme_settings_path' => resource_path('theme-settings.json'),
```

### Accessing theme settings in Blade

```blade
{{-- Via the Theme facade --}}
@php $themeSettings = app(\Coderstm\PageBuilder\Services\ThemeSettings::class) @endphp

<body style="--primary: {{ $themeSettings->get('primary_color', '#1d4ed8') }}">
```

---

## Asset Loading

### Vite (recommended)

```php
// helpers.php
theme_vite(['css/theme.css', 'js/theme.js'])
```

This resolves assets through the active theme's `vite.config.js` manifest.

### Mix

```php
// helpers.php — legacy Mix support
theme_mix('css/theme.css')
```

---

## Theme Shadowing Rules

When the same section/block type is registered by both the built-in package AND a theme:

1. **Built-in** is registered first (during `ServiceProvider::boot()`).
2. **Theme** is registered second.
3. **Last registration wins** — the theme version is used.

This allows themes to completely replace `row.blade.php`, `column.blade.php`, or any built-in section with a custom implementation.

---

## Page JSON for Themes

Page JSON files live at `themes/{name}/views/pages/{slug}.json` (or `resources/views/pages/` for the default theme):

```json
{
    "sections": {
        "header-hero": {
            "type": "hero",
            "settings": {
                "title": "Welcome to My Theme",
                "subtitle": "Built with Laravel Page Builder"
            },
            "blocks": {},
            "order": []
        },
        "features": {
            "type": "features",
            "settings": { "heading": "Why Choose Us" },
            "blocks": {
                "f1": { "type": "feature-item", "settings": { "title": "Fast" }, "blocks": {}, "order": [] },
                "f2": { "type": "feature-item", "settings": { "title": "Secure" }, "blocks": {}, "order": [] },
                "f3": { "type": "feature-item", "settings": { "title": "Flexible" }, "blocks": {}, "order": [] }
            },
            "order": ["f1", "f2", "f3"]
        }
    },
    "order": ["header-hero", "features"],
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": { "sticky": true },
                "blocks": {
                    "nav-home":    { "type": "nav-item", "settings": { "label": "Home",    "url": "/" },        "blocks": {}, "order": [] },
                    "nav-about":   { "type": "nav-item", "settings": { "label": "About",   "url": "/about" },   "blocks": {}, "order": [] },
                    "nav-contact": { "type": "nav-item", "settings": { "label": "Contact", "url": "/contact" }, "blocks": {}, "order": [] }
                },
                "order": ["nav-home", "nav-about", "nav-contact"]
            },
            "footer": {
                "type": "site-footer",
                "settings": {},
                "blocks": {},
                "order": []
            }
        }
    }
}
```

---

## Theme Development Checklist

- [ ] Create `themes/{name}/views/layouts/page.blade.php` with `@sections('header')` and `@sections('footer')`
- [ ] Add `@pbEditorClass` to `<html>` tag
- [ ] Create sections in `themes/{name}/views/sections/` with `@schema()` directives
- [ ] Create blocks in `themes/{name}/views/blocks/` with `@schema()` directives
- [ ] Define `theme_settings_schema` in `config/pagebuilder.php`
- [ ] Create page JSON files in `themes/{name}/views/pages/`
- [ ] Register the theme in `config/themer.php`

---

## Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Section Blade file | `kebab-case.blade.php` | `site-header.blade.php` |
| Block Blade file | `kebab-case.blade.php` | `image-text.blade.php` |
| Setting `id` | `snake_case` | `background_color` |
| Page JSON file | `kebab-case.json` | `landing-page.json` |
| Layout slot key | `kebab-case` | `header`, `footer`, `top-bar` |
| Section type | Matches Blade filename | `site-header`, `image-text` |
