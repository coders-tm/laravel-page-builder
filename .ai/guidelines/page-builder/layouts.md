# Laravel Page Builder — Page JSON & Layout Structure

---

## Page JSON Document

Every page is backed by a JSON document stored in `config('pagebuilder.pages')/{slug}.json`.

### Minimal structure

```json
{
    "sections": {
        "hero": {
            "type": "hero",
            "settings": {},
            "blocks": {},
            "order": []
        }
    },
    "order": ["hero"]
}
```

### Full structure with nested blocks

```json
{
    "sections": {
        "hero": {
            "type": "section",
            "settings": {
                "padding_top": "lg",
                "max_width": "7xl",
                "background_color": "#f5f5f5"
            },
            "blocks": {
                "row1": {
                    "type": "row",
                    "settings": { "columns": "2", "gap": "md" },
                    "blocks": {
                        "col-left": {
                            "type": "column",
                            "settings": { "padding": "md" },
                            "blocks": {},
                            "order": []
                        },
                        "col-right": {
                            "type": "column",
                            "settings": { "padding": "md" },
                            "blocks": {},
                            "order": []
                        }
                    },
                    "order": ["col-left", "col-right"]
                }
            },
            "order": ["row1"],
            "disabled": false
        },
        "cta": {
            "type": "cta",
            "settings": { "title": "Get Started" },
            "blocks": {},
            "order": [],
            "disabled": false
        }
    },
    "order": ["hero", "cta"]
}
```

---

## Field Reference

### Top-level fields

| Field | Type | Required | Description |
|---|---|---|---|
| `sections` | object | yes | Map of section instances, keyed by unique ID |
| `order` | string[] | yes | Render order of section IDs |
| `layout` | object | no | Per-page layout slot overrides (header/footer) |

### Section instance fields

| Field | Type | Required | Description |
|---|---|---|---|
| `type` | string | yes | Matches a registered `SectionSchema` key (Blade filename without `.blade.php`) |
| `settings` | object | yes | Key→value setting overrides; missing keys fall back to schema defaults |
| `blocks` | object | yes | Map of block instances, keyed by unique ID |
| `order` | string[] | yes | Render order of block IDs within this section |
| `disabled` | boolean | no | When `true`, section is hidden from the rendered output |
| `_name` | string | no | Custom display label overriding the schema name in the editor |

### Block instance fields

| Field | Type | Required | Description |
|---|---|---|---|
| `type` | string | yes | Matches a registered `BlockSchema` key or a local block definition |
| `settings` | object | yes | Key→value setting overrides |
| `blocks` | object | yes | Nested child block instances (for container blocks like `row`) |
| `order` | string[] | yes | Render order of child block IDs |
| `disabled` | boolean | no | When `true`, block is excluded from the rendered output |

---

## Layout Sections (Header / Footer)

The `layout` key holds per-page overrides for structural slots rendered **outside** the main `@yield` area — typically header and footer zones defined by the active Blade layout.

```json
{
    "sections": { "...": "..." },
    "order": ["hero", "cta"],
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": { "sticky": true },
                "blocks": {},
                "order": [],
                "disabled": false
            },
            "footer": {
                "type": "site-footer",
                "settings": { "show_social": true },
                "blocks": {},
                "order": [],
                "disabled": false
            }
        }
    }
}
```

### Layout section rules

- `layout.sections` is keyed by **position slug** (`"header"`, `"footer"`, etc.), not a random ID.
- Keys that match `"header"` or carry `position: "top"` render in the **top zone**.
- All other keys fall to the **bottom zone**.
- `disabled: true` causes `@sections('key')` to return an empty string.
- `_name` is supported to override the schema display name in the editor.
- Layout sections are **not sortable** — their position is determined by the Blade layout.

### Blade layout integration

In the site Blade layout file, use `@sections('header')` and `@sections('footer')` to render layout slots:

```blade
<!DOCTYPE html>
<html>
<head>...</head>
<body>
    @sections('header')    {{-- renders layout.sections.header --}}

    @yield('content')      {{-- rendered page sections go here --}}

    @sections('footer')    {{-- renders layout.sections.footer --}}
</body>
</html>
```

---

## Nesting Depth

Blocks can nest to any depth. The `Renderer` hydrates them recursively via `hydrateBlocks()`:

```
Section
  └── Row (block)
        ├── Column (block)
        │     └── Card (block)
        └── Column (block)
```

Each level uses `blocks` (map) + `order` (array) for deterministic render order.

---

## Settings Resolution

When rendering, `Settings` resolves values in this priority order:

1. Value stored in page JSON for this instance.
2. Default value from `SettingSchema` (defined in `@schema` directive).
3. `null` if no default exists.

You never need to store every setting — only overrides from the schema default.

---

## Disabled Filtering

Disabled blocks are **silently excluded** during hydration — they never appear in `BlockCollection` and never render. This is handled inside `Renderer::hydrateBlocks()`.

Disabled sections are excluded by `SectionCollection::enabled()` before rendering.

---

## Page JSON — PHP Access

```php
use Coderstm\PageBuilder\Support\PageData;

$pageData = PageData::fromArray($rawJson);

$pageData->sections;          // array of raw section data
$pageData->order;             // string[] render order
$pageData->layout;            // ?array layout zones
$pageData->toArray();         // back to raw array for storage
```

---

## Creating / Saving a Page

```php
use Coderstm\PageBuilder\Services\PageStorage;

$storage = app(PageStorage::class);

// Load
$json = $storage->load('home');    // returns PageData

// Save
$storage->save('home', $pageData->toArray());
```

---

## Example: Minimal Hero Page

```json
{
    "sections": {
        "main-hero": {
            "type": "hero",
            "settings": {
                "title": "Welcome to Our Site",
                "subtitle": "We build amazing things",
                "background_image": "/storage/pagebuilder/hero-bg.jpg"
            },
            "blocks": {},
            "order": []
        }
    },
    "order": ["main-hero"]
}
```

## Example: Landing Page with Sections + Layout

```json
{
    "sections": {
        "s1": {
            "type": "section",
            "settings": { "padding_top": "xl", "padding_bottom": "xl" },
            "blocks": {
                "b1": { "type": "row", "settings": { "columns": "2" }, "blocks": {}, "order": [] }
            },
            "order": ["b1"]
        },
        "s2": {
            "type": "cta",
            "settings": { "title": "Ready to Start?" },
            "blocks": {},
            "order": []
        }
    },
    "order": ["s1", "s2"],
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": { "sticky": false },
                "blocks": {},
                "order": []
            }
        }
    }
}
```
