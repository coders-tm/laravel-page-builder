# Laravel Page Builder — Templates

---

## Overview

Templates are **JSON fallback layouts** for pages that have no per-page JSON file (`pages/{slug}.json`) and no custom Blade view (`pages/{slug}.blade.php`). They work like Shopify's JSON templates: a single JSON file defines the sections, render order, optional wrapper element, and layout type for any page that uses it.

A page selects its template via the `template` column on the `Page` model. When no template is selected, the default `page.json` template is used.

---

## Page Rendering Resolution Order

```
1. Editor mode              → always renders from page JSON (bypasses all below)
2. pages/{slug}.blade.php   → custom Blade view wins if it exists
3. pages/{slug}.json        → stored page builder JSON wins if it exists
4. templates/{name}.json    → template selected by Page::$template, or page.json default
5. 404
```

Templates are **only consulted** when steps 2 and 3 both miss. A template never overrides an existing page JSON.

---

## Template JSON Schema

```json
{
    "layout":   "page",
    "wrapper":  "main#content.container",
    "sections": {
        "main": {
            "type": "page-content",
            "settings": {}
        }
    },
    "order": ["main"]
}
```

### Root fields

| Field | Type | Required | Description |
|---|---|---|---|
| `sections` | object | yes | Section data map — same format as page JSON sections |
| `order` | string[] | yes | Section render order — IDs must exist in `sections` |
| `layout` | string \| false | no | Layout type for `LayoutParser`. Defaults to `"page"`. `false` disables layout zones (no header/footer) |
| `wrapper` | string | no | CSS-selector string to wrap rendered sections in an HTML element |

### Template vs Page JSON differences

| Aspect | Page JSON | Template JSON |
|---|---|---|
| `layout` | Complex object `{type, header, footer}` | Simple string (type name) or `false` |
| `wrapper` | Not supported | Optional HTML wrapper around all sections |
| Storage location | `config('pagebuilder.pages')/{slug}.json` | `config('pagebuilder.templates')/{name}.json` |
| Mutated by editor | Yes | No — templates are read-only at runtime |

---

## Template Naming

Files live in `config('pagebuilder.templates')` (default: `resources/views/templates/`).

| Filename | Template name | DB `template` field value |
|---|---|---|
| `page.json` | Default page template | `null` / `""` / `"page"` |
| `page.alternate.json` | Alternate page template | `"page.alternate"` |
| `product.json` | Product template | `"product"` |

Rules:
- The `.json` extension is always stripped when looking up by name.
- Template names are normalised to lowercase before lookup.
- If a requested template file does not exist, `TemplateStorage` returns `null` and `PageService` falls back to `page.json`. If `page.json` also does not exist, the request returns 404.

---

## The `wrapper` Property

The `wrapper` field accepts a CSS-selector-like string and wraps all rendered section HTML in a single element.

### Syntax

```
tag#id.class1.class2[attr1=val1][attr2=val2]
```

- **Tag** — `div`, `main`, or `section` (defaults to `div` for any other value)
- **`#id`** — sets the element `id` attribute
- **`.class`** — sets the element `class` attribute (multiple classes joined with spaces)
- **`[key=value]`** — sets arbitrary HTML attributes

### Example

```json
{
    "wrapper": "div#div_id.div_class[attribute-one=value]"
}
```

Output:
```html
<div id="div_id" class="div_class" attribute-one="value">
    <!-- rendered sections -->
</div>
```

### Supported wrapper tags

Only these three HTML elements are accepted as the wrapper tag:

| Tag | Usage |
|---|---|
| `<div>` | Generic container (default) |
| `<main>` | Page main content landmark |
| `<section>` | Thematic section grouping |

Any other tag string results in `<div>` being used.

---

## Variable Interpolation

Template section settings can embed `{{ $page->attribute }}` placeholders. At render time, `TemplateVariableResolver` replaces these with the corresponding attribute from the `Page` Eloquent model.

### Syntax

```json
{
    "sections": {
        "hero": {
            "type": "hero",
            "settings": {
                "title":       "{{ $page->title }}",
                "description": "{{ $page->meta_description }}"
            }
        }
    }
}
```

- Whitespace around the expression is ignored: `{{$page->title}}` and `{{ $page->title }}` are both valid.
- Only `$page->attribute` access is supported — no method calls or expressions.
- If the attribute is `null` or does not exist, the placeholder resolves to an empty string.
- When there is no DB page (guest page without a model), all placeholders resolve to `""`.
- Non-page placeholders (e.g. `{{ $other->title }}`) are left unchanged.

### Available page attributes

Any column on your `Page` model is accessible:

| Attribute | Type | Notes |
|---|---|---|
| `title` | string | Page title |
| `slug` | string | URL slug |
| `content` | string | Page body HTML |
| `meta_title` | string\|null | SEO title |
| `meta_description` | string\|null | SEO description |
| `meta_keywords` | string\|null | SEO keywords |
| `template` | string\|null | Template name |
| Any custom column | mixed | Cast to string on substitution |

---

## Theme-Aware Template Resolution

`TemplateStorage::load()` checks the active theme path before falling back to the configured templates directory.

Resolution order:

```
1. Theme::path('views/templates/{name}.json')   → active theme directory
2. config('pagebuilder.templates')/{name}.json  → app templates directory
```

This means a theme can override the default `page.json` template by providing `views/templates/page.json` inside the theme directory. Per-theme templates shadow app-level templates using the same shadowing rules as sections and blocks.

---

## Configuration

```php
// config/pagebuilder.php
'templates' => resource_path('views/templates'),
```

---

## Key Classes

| Class | Path | Responsibility |
|---|---|---|
| `TemplateStorage` | `src/Services/TemplateStorage.php` | Loads raw template JSON (theme-aware) |
| `TemplateVariableResolver` | `src/Support/TemplateVariableResolver.php` | Resolves `{{ $page->attr }}` in template data |
| `WrapperParser` | `src/Support/WrapperParser.php` | Parses CSS-selector wrapper strings into HTML |
| `PageService` | `src/Services/PageService.php` | Orchestrates template resolution as step 4 |
| `PageRenderer` | `src/Services/PageRenderer.php` | Applies wrapper after rendering page sections |

---

## Data Flow

```
Request /about

  ↓ PageService::render('about')
  ↓ No pages/about.blade.php, No pages/about.json
  ↓ PageService::resolveTemplate($dbPage)
        ↓ TemplateStorage::load($dbPage->template ?? 'page')
        ↓ Returns raw array or null (falls back to page.json)
  ↓ TemplateVariableResolver::resolve($rawData, $dbPage)
        ↓ Replaces {{ $page->title }} with actual values
  ↓ PageService::resolveTemplateLayout($resolvedData)
        ↓ layout: "page"  → LayoutParser::defaultLayout('page')
        ↓ layout: false   → []  (no header/footer zones)
  ↓ PageService::buildPageFromTemplate($resolvedData, $defaultLayout, $dbPage)
        ↓ PageData::fromArray([sections, order, wrapper, title], $defaultLayout)
  ↓ PageRenderer::renderPage($pageData)
        ↓ Renders each section via Renderer
        ↓ If wrapper set → WrapperParser::render($wrapper, $sectionsHtml)
  ↓ view('pagebuilder::page', [...])
```

---

## Common Pitfalls

- **Template ignored when JSON exists** — if `pages/{slug}.json` exists for a page, the template is never consulted. Delete or do not create a per-page JSON for template-only pages.
- **`layout: false` skips header/footer** — when set, `LayoutParser` is not called and `$defaultLayout` is empty, so `@sections('header')` and `@sections('footer')` in the Blade layout will render nothing.
- **Wrapper tag not in allowed list** — `article`, `div[...`, or other tags fall back to `<div>`. Only `div`, `main`, `section` are accepted.
- **Circular variable reference** — `{{ $page->template }}` resolves to the template name string (e.g. `"page.alternate"`), not to any rendered content.
- **Non-string placeholder values** — numbers and booleans on the model are cast to string (e.g. `true` → `"1"`).

---

## Do / Don't

| Do | Don't |
|---|---|
| Store templates in `resources/views/templates/` | Name templates with `.blade.php` — templates are JSON only |
| Use `page.json` as the universal default | Rely on template fallback in editor mode — editor always uses page JSON |
| Use `{{ $page->title }}` for dynamic text | Use PHP expressions like `{{ strtoupper($page->title) }}` — only property access is supported |
| Use `layout: false` to opt out of header/footer | Leave `layout` absent when you do want the default layout |
| Override templates per-theme in `views/templates/` | Modify `TemplateStorage` or `PageService` to add custom resolution logic |
