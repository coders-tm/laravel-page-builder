---
title: Themes
---

# Themes

A theme is a collection of Blade views, assets, and configurations that define the look and feel of your site. Themes allow you to create reusable, customizable designs.

## What is a Theme?

A theme is responsible for:

- Providing the base `layouts/`
- Defining the visual appearance of `sections/`
- Implementing reusable `blocks/`
- Providing CSS/JS assets for the frontend

## Theme Structure

```
themes/my-theme/
├── views/
│   ├── layouts/
│   │   └── page.blade.php
│   ├── sections/
│   │   ├── hero.blade.php
│   │   ├── features.blade.php
│   │   └── footer.blade.php
│   ├── blocks/
│   │   ├── row.blade.php
│   │   ├── column.blade.php
│   │   └── text.blade.php
│   └── templates/
│       └── page.json
├── assets/
│   ├── css/
│   │   └── theme.css
│   └── js/
│       └── theme.js
└── config.json
```

## Creating a Theme

### 1. Create Theme Directory

```bash
mkdir -p themes/my-theme/{views/layouts,views/sections,views/blocks,views/templates}
mkdir -p themes/my-theme/{assets/css,assets/js}
```

### 2. Create Layout

```blade
{{-- themes/my-theme/views/layouts/page.blade.php --}}
<html @editor('dark') lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta_title ?? ($title ?? '') . ' | ' . config('app.name') }}</title>
    <link rel="stylesheet" href="{{ theme('css/theme.css') }}">
</head>
<body class="page-layout">

    @sections('header')

    <main>
        @yield('content')
    </main>

    @sections('footer')

    <script src="{{ theme('js/theme.js') }}"></script>
</body>
</html>
```

### 3. Create Sections

```blade
{{-- themes/my-theme/views/sections/hero.blade.php --}}
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => ''],
        ['id' => 'bg_color', 'type' => 'color', 'label' => 'Background', 'default' => '#6366f1'],
    ],
])

<section {!! $section->editorAttributes() !!}
    class="hero-section"
    style="background-color: {{ $section->settings->bg_color }}">
    <div class="container">
        <h1>{{ $section->settings->title }}</h1>
        <p>{{ $section->settings->subtitle }}</p>
    </div>
</section>
```

### 4. Create Blocks

```blade
{{-- themes/my-theme/views/blocks/row.blade.php --}}
@schema([
    'name' => 'Row',
    'settings' => [
        ['id' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '2',
         'options' => [
             ['value' => '1', 'label' => '1 Column'],
             ['value' => '2', 'label' => '2 Columns'],
             ['value' => '3', 'label' => '3 Columns'],
         ]],
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

### 5. Create Template

```json
{
  "sections": {
    "main": {
      "type": "page-content"
    }
  },
  "order": ["main"]
}
```

## Theme Assets

### Using the `theme()` Helper

```blade
{{-- CSS --}}
<link rel="stylesheet" href="{{ theme('css/theme.css') }}">

{{-- JS --}}
<script src="{{ theme('js/theme.js') }}"></script>

{{-- Images --}}
<img src="{{ theme('images/logo.png') }}" alt="Logo">
```

### Vite Integration

```blade
{{-- For Vite-based themes --}}
@vite(['themes/my-theme/css/theme.css', 'themes/my-theme/js/theme.js'])
```

## Theme Settings

Themes can have global settings. Values are stored in `themes/my-theme/config.json` under a `pagebuilder` key:

```json
// themes/my-theme/config.json
{
  "pagebuilder": {
    "colors.primary": "#6366f1",
    "colors.secondary": "#4f46e5",
    "fonts.body": "Inter, sans-serif"
  }
}
```

The schema for these settings is defined in `config/pagebuilder.php` under `theme_settings_schema`. See [Configuration](/configuration#theme_settings_schema) for the full schema reference.

### Accessing Theme Settings

`$theme` is a `ThemeSettings` instance shared with all Blade views, providing type-safe getters (`getString`, `getInt`, `getBool`, `getArray`, `getFloat`):

```blade
<style>
    :root {
        --color-primary: {{ $theme->getString('colors.primary', '#6366f1') }};
        --font-body: {{ $theme->getString('fonts.body', 'Inter, sans-serif') }};
    }
</style>
```

## Activating a Theme

### Via Configuration

```php
// config/pagebuilder.php
return [
    'theme' => 'my-theme',
];
```

### Via Middleware

```php
// In route or controller
Route::get('/page/{slug}', [PageController::class, 'show'])
    ->middleware(\PageBuilder\Http\Middleware\ThemeMiddleware::class . ':my-theme');
```

### Via Facade

```php
use PageBuilder\Facades\Theme;

Theme::set('my-theme');
```

## Theme Overriding

Themes can override package defaults:

```
themes/my-theme/views/
├── sections/           # Override package sections
│   └── hero.blade.php
├── blocks/             # Override package blocks
│   └── row.blade.php
└── templates/          # Override package templates
    └── page.json
```

## Theme Discovery

The system discovers themes in this order:

1. **Theme Views** — `themes/{theme}/views/`
2. **App Views** — `resources/views/`
3. **Package Views** — Package's `resources/views/`

Last registration wins.

## Multi-Theme Support

::: tip Premium Content
Multi-theme system documentation is available to sponsors only.
:::

```php
// Example: Dynamic theme switching
use PageBuilder\Facades\Theme;

// Switch theme based on user preference
Theme::set($user->theme);

// Or based on route
Theme::set(Route::currentRouteName());
```

### Tips

1. **Keep themes modular** — Separate concerns into sections and blocks
2. **Use presets** — Provide common configurations as presets
3. **Asset versioning** — Use file modification timestamps for cache busting
4. **Responsive design** — Make themes responsive by default
5. **Editor support** — Always include `@editor` and `editorAttributes()`
