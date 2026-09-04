---
title: Layouts
---

# Layouts

Layouts define the structural wrapper for your page content. They control the header, footer, and other zones that surround the main content area.

## What is a Layout?

A layout is a Blade template that defines the overall structure of your pages. It includes:

- **Header** — Site navigation, logo, and other header elements
- **Content** — The main page content area (`@yield('content')`)
- **Footer** — Site footer, copyright, and other footer elements

Layout files live in `resources/views/layouts/` and are resolved by name (e.g. `layouts.page` for the `page` type).

## Creating a Layout

### Basic Layout

Create a layout Blade file:

```blade
{{-- resources/views/layouts/page.blade.php --}}
<html @pbEditorClass('dark') lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta_title ?? ($title ?? '') . ' | ' . config('app.name') }}</title>
    <meta name="description" content="{{ $meta_description ?? '' }}">
</head>
<body class="page-layout">

    @sections('header')

    <main class="min-h-screen">
        @yield('content')
    </main>

    @sections('footer')
</body>
</html>
```

### The `@sections` Directive

`@sections` is a **custom page builder directive** (not standard Blade `@section`). It is self-closing.

```blade
@sections('header')
@sections('footer')
@sections('announcement')
```

It renders a **layout section** from the page JSON data. The content comes from the `layout.header` or `layout.footer` zones defined in the page JSON — not from inline Blade.

The directive searches the header zone first, then the footer zone, matching by key. For example, `@sections('header')` renders whatever section is defined under `layout.header.sections.header` in the page JSON.

### Available Variables

Layout views receive these variables automatically:

| Variable            | Description                                             |
| ------------------- | ------------------------------------------------------- |
| `$title`            | Page title from the database                            |
| `$meta_title`       | SEO meta title                                          |
| `$meta_description` | SEO meta description                                    |
| `$meta_keywords`    | SEO meta keywords                                       |
| `$page`             | Page Eloquent model (shared globally via `View::share`) |

## Per-Page Layouts

Pages can define custom layout sections in their JSON. The `layout` key controls which Blade layout file to use and what sections to render in the header/footer zones:

```json
{
  "layout": {
    "type": "page",
    "header": {
      "sections": {
        "header": {
          "type": "site-header",
          "settings": { "sticky": true },
          "blocks": {},
          "order": [],
          "disabled": false
        }
      }
    },
    "footer": {
      "sections": {
        "footer": {
          "type": "site-footer",
          "settings": {},
          "blocks": {},
          "order": [],
          "disabled": false
        }
      }
    }
  },
  "sections": {
    "main": {
      "type": "page-content",
      "settings": {},
      "blocks": {},
      "order": [],
      "disabled": false
    }
  },
  "order": ["main"]
}
```

When the editor saves this data, `@sections('header')` in your layout Blade file renders the `site-header` section, and `@sections('footer')` renders the `site-footer` section.

## Layout Zones

| Zone      | Description       | How to render         |
| --------- | ----------------- | --------------------- |
| `header`  | Site header area  | `@sections('header')` |
| `footer`  | Site footer area  | `@sections('footer')` |
| `content` | Main page content | `@yield('content')`   |

The `header` and `footer` zones are rendered by `@sections`. The `content` zone is standard Blade — defined with `@section('content')` in `pagebuilder::page` and yielded with `@yield('content')` in your layout.

## Layout Types

The `layout.type` field in the page JSON maps to a Blade view name: `layouts.{type}`.

### Page Layout

The default layout type:

```json
{
  "layout": {
    "type": "page"
  }
}
```

This loads `resources/views/layouts/page.blade.php`.

### Custom Layout Types

Define custom layout types by creating additional Blade files:

```blade
{{-- resources/views/layouts/simple.blade.php --}}
<html @pbEditorClass('dark') lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $meta_title ?? ($title ?? '') . ' | ' . config('app.name') }}</title>
</head>
<body class="simple-layout">

    @sections('announcement')
    @sections('header')

    <main class="min-h-screen">
        @yield('content')
    </main>

    @sections('footer')
</body>
</html>
```

```json
{
  "layout": {
    "type": "simple"
  }
}
```

### No Layout

Set `"layout": false` to render without header/footer zones:

```json
{
  "layout": false,
  "sections": {
    "main": { "type": "hero" }
  },
  "order": ["main"]
}
```

## Shared Layout Settings

Layout configurations can be **shared across multiple pages** instead of being duplicated in each page's JSON. This is managed by the `LayoutSettings` service.

### How It Works

Layout configs are stored in `settings.json` (configured via `theme_settings_path`) under the `_pagebuilder.layouts.{type}` key:

```json
{
  "pagebuilder": {
    "colors.primary": "hsl(168 94% 7%)"
  },
  "_pagebuilder": {
    "layouts": {
      "page": {
        "header": {
          "sections": {
            "header": {
              "type": "site-header",
              "settings": { "sticky": true },
              "blocks": {},
              "order": []
            }
          },
          "order": ["header"]
        },
        "footer": {
          "sections": {
            "footer": {
              "type": "site-footer",
              "settings": { "copyright": "2026" },
              "blocks": {},
              "order": []
            }
          },
          "order": ["footer"]
        }
      }
    }
  }
}
```

### Page JSON Layout Formats

The `layout` field in page JSON supports two formats:

#### String Format (Shared Layout)

```json
{
  "layout": "page"
}
```

When `layout` is a string, the page **inherits the shared layout** from `LayoutSettings`. The editor displays the full layout config from `_pagebuilder.layouts.page`.

#### Object Format (Page-Specific Override)

```json
{
  "layout": {
    "type": "page",
    "header": {
      "sections": {
        "header": {
          "type": "site-header",
          "settings": { "logo": "/custom.png" }
        }
      },
      "order": ["header"]
    },
    "footer": {
      "sections": {},
      "order": []
    }
  }
}
```

When `layout` is an object, the page has a **page-specific override**. The full layout config is stored in the page JSON.

#### Missing Layout

If the `layout` key is missing, it defaults to the shared `"page"` layout.

### Layout Resolution Priority

Layout data is merged from three layers (lowest to highest priority):

1. **Default** — Schema defaults from `LayoutParser` (from `@schema()` in layout Blade files)
2. **Shared** — Layout config from `LayoutSettings` (`_pagebuilder.layouts.{type}`)
3. **Page-specific** — Layout object from page JSON (if `layout` is an object)

Example: If the shared layout sets `header.settings.logo = "/default.png"` and a page's layout sets `header.settings.logo = "/custom.png"`, the page will use `/custom.png`.

### How Layout Saving Works

When the editor saves a page, `PageStorage::save()` determines where to store the layout based on the **existing** page.json:

| Existing page.json layout | Save behavior                                                       |
| ------------------------- | ------------------------------------------------------------------- |
| Not exists                | Save layout to `LayoutSettings`, store `"page"` string in page JSON |
| String (`"page"`)         | Save layout to `LayoutSettings`, store string in page JSON          |
| Object (`{...}`)          | Save full layout object to page JSON, strip `source` key            |

This means:

- **New pages** automatically get shared layouts
- **Existing pages with string layouts** continue to use shared layouts
- **Existing pages with object layouts** keep their page-specific overrides

### LayoutSettings Service

The `LayoutSettings` service manages shared layout configurations:

```php
use PageBuilder\Services\LayoutSettings;

$layoutSettings = app(LayoutSettings::class);

// Get all layout configs
$all = $layoutSettings->all();

// Get a specific layout as raw array
$pageLayout = $layoutSettings->get('page');

// Get a specific layout as a type-safe LayoutConfig DTO
$layoutConfig = $layoutSettings->getConfig('page');

// Save a layout config
$layoutSettings->save('page', [
    'header' => [
        'sections' => [
            'header' => [
                'type' => 'site-header',
                'settings' => ['sticky' => true],
                'blocks' => [],
                'order' => [],
            ],
        ],
        'order' => ['header'],
    ],
    'footer' => [
        'sections' => [],
        'order' => [],
    ],
]);

// Delete a layout config
$layoutSettings->delete('page');

// Flush cache (after saving)
$layoutSettings->flush();
```

### Layout Editor Source

When the editor loads a page, the API response includes a `source` field in the layout object:

```json
{
  "layout": {
    "type": "page",
    "source": "shared",
    "header": { ... },
    "footer": { ... }
  }
}
```

| Source     | Meaning                                   |
| ---------- | ----------------------------------------- |
| `"shared"` | Layout is inherited from `LayoutSettings` |
| `"page"`   | Layout is a page-specific override        |

This field is **editor metadata** — it is stripped on save and never persisted to disk.

## Layout Resolution

The system resolves layouts in this order:

1. **Page JSON** — `layout.type` in the page data selects the Blade view
2. **Theme Layout** — Layout from the active theme's `views/layouts/` directory
3. **App Layout** — Layout from `resources/views/layouts/`
4. **Default** — Package default layout

## Tips

1. **Keep layouts simple** — Don't add complex logic to layouts
2. **Use `@sections`** — Self-closing directive for header/footer zones, content comes from page JSON
3. **Responsive design** — Make layouts responsive by default
4. **SEO basics** — Include `$meta_title`, `$meta_description` in the `<head>`
5. **Editor support** — Always include `@pbEditorClass` on the `<html>` tag
6. **Use shared layouts** — Store common header/footer configs in `LayoutSettings` to avoid duplicating across pages
