---
title: Blade Directives
---

# Blade Directives Reference

Laravel Page Builder provides several Blade directives for rendering sections, blocks, and layouts.

## @schema

Declares the schema for a section or block. This directive is a no-op at render time — it's only used during registration to extract schema information.

### Syntax

```blade
@schema([
    'name' => 'Section Name',
    'settings' => [...],
    'blocks' => [...],
    'presets' => [...],
])
```

### Usage

```blade
{{-- In a section file --}}
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
</section>
```

## @blocks

Renders all blocks within a section or container block.

### Syntax

```blade
@blocks($section)
@blocks($block)
```

### Usage

```blade
{{-- In a section file --}}
<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    @blocks($section)
</section>

{{-- In a container block file --}}
<div {!! $block->editorAttributes() !!}>
    @blocks($block)
</div>
```

## @sections

Renders layout zones (header, footer, etc.) from the page JSON data. This is a **custom page builder directive** — not standard Blade `@section`. It is self-closing.

### Syntax

```blade
@sections('zone_name')
```

### Usage

```blade
{{-- In a layout file --}}
@sections('header')

<main>
    @yield('content')
</main>

@sections('footer')
```

## @pbEditorClass

Renders the `<html>` class attribute with editor mode classes.

### Syntax

```blade
@pbEditorClass
```

### Usage

```blade
{{-- In a layout file --}}
<html {!! @pbEditorClass !!}>
<head>
    <title>{{ $page->title }}</title>
</head>
<body {!! $page->editorAttributes() !!}>
    {{-- Content --}}
</body>
</html>
```

### Output

When editor is active:

```html
<html class="pb-editor"></html>
```

When editor is inactive:

```html
<html></html>
```

## @pbThemeFont

Renders Google Font links for theme typography settings.

### Syntax

```blade
@pbThemeFont
```

### Usage

```blade
{{-- In a layout file --}}
<html>
<head>
    <title>{{ $page->title }}</title>
    @pbThemeFont
</head>
<body>
    {{-- Content --}}
</body>
</html>
```

### Output

```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
  rel="stylesheet"
/>
```

## Helper Functions

### pb_editor()

Checks if editor mode is active.

```php
if (pb_editor()) {
    // Editor is active
}
```

### theme()

Returns the URL for a theme asset.

```php
$url = theme('css/theme.css');
$url = theme('js/theme.js');
$url = theme('images/logo.png');
```

### theme_vite()

Returns Vite-processed URLs for theme assets.

```php
$url = theme_vite('resources/css/theme.css');
$url = theme_vite('resources/js/theme.js');
```

## Component Variables

### Section Variables

In section Blade files, these variables are available:

| Variable                       | Type              | Description             |
| ------------------------------ | ----------------- | ----------------------- |
| `$section`                     | `Section`         | The section instance    |
| `$section->id`                 | `string`          | Unique instance ID      |
| `$section->type`               | `string`          | Section type (filename) |
| `$section->name`               | `string`          | Human-readable name     |
| `$section->settings`           | `Settings`        | Settings object         |
| `$section->blocks`             | `BlockCollection` | Child blocks            |
| `$section->editorAttributes()` | `string`          | Editor data attributes  |

### Block Variables

In block Blade files, these variables are available:

| Variable                     | Type              | Description                       |
| ---------------------------- | ----------------- | --------------------------------- |
| `$block`                     | `Block`           | The block instance                |
| `$block->id`                 | `string`          | Unique instance ID                |
| `$block->type`               | `string`          | Block type (filename)             |
| `$block->settings`           | `Settings`        | Settings object                   |
| `$block->blocks`             | `BlockCollection` | Child blocks                      |
| `$block->editorAttributes()` | `string`          | Editor data attributes            |
| `$section`                   | `Section`         | Parent section (always available) |

### Layout Variables

In layout Blade files, these variables are available:

| Variable                    | Type       | Description            |
| --------------------------- | ---------- | ---------------------- |
| `$page`                     | `PageData` | The page data object   |
| `$page->title`              | `string`   | Page title             |
| `$page->slug`               | `string`   | Page slug              |
| `$page->meta_title`         | `string`   | SEO title              |
| `$page->meta_description`   | `string`   | SEO description        |
| `$page->editorAttributes()` | `string`   | Editor data attributes |

## Complete Example

```blade
{{-- resources/views/layouts/page.blade.php --}}
<html @pbEditorClass('dark') lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta_title ?? ($title ?? '') . ' | ' . config('app.name') }}</title>
    <meta name="description" content="{{ $meta_description ?? '' }}">
    @pbThemeFont
</head>
<body class="page-layout">

    @sections('header')

    <main>
        @yield('content')
    </main>

    @sections('footer')
</body>
</html>
```

```blade
{{-- resources/views/sections/hero.blade.php --}}
@schema([
    'name' => 'Hero',
    'settings' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Welcome'],
        ['id' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle', 'default' => ''],
    ],
])

<section {!! $section->editorAttributes() !!}>
    <h1>{{ $section->settings->title }}</h1>
    @if($section->settings->subtitle)
        <p>{{ $section->settings->subtitle }}</p>
    @endif
    @blocks($section)
</section>
```

```blade
{{-- resources/views/blocks/text.blade.php --}}
@schema([
    'name' => 'Text',
    'settings' => [
        ['id' => 'content', 'type' => 'richtext', 'label' => 'Content', 'default' => ''],
    ],
])

<div {!! $block->editorAttributes() !!}>
    {!! $block->settings->content !!}
</div>
```

## Tips

1. **Always include @pbEditorClass** — In layout `<html>` tag
2. **Always include editorAttributes()** — On sections and blocks
3. **Use @blocks for nesting** — In container blocks and sections
4. **Use @sections for zones** — In layout files
5. **Check pb_editor()** — For editor-only content
