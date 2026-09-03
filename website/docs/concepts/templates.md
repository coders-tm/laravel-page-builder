---
title: Templates
---

# Templates

Templates are **JSON fallback layouts** for pages that have no per-page page builder JSON file and no custom Blade view. They let you define a single file that controls which sections a whole category of pages renders.

## What is a Template?

Templates provide a default layout for pages that don't have custom content. They're useful for:

- Default page structures
- Category-based layouts
- Fallback content

## Page Resolution Order

```
1. Custom Blade view    pages/{slug}.blade.php   (highest priority)
2. Page builder JSON    pages/{slug}.json
3. Template JSON        templates/{template}.json or templates/page.json
4. 404
```

Templates are only consulted when both step 1 and step 2 miss. A template never overrides an existing page JSON.

## Creating a Template

### Default Template

Place template files in `resources/views/templates/` (configurable via `config('pagebuilder.templates')`).

```json
// resources/views/templates/page.json
{
  "sections": {
    "main": {
      "type": "page-content"
    }
  },
  "order": ["main"]
}
```

The `page.json` file is the **default template**. Any page without a page JSON, and without a specific template selected, renders through it.

### Template JSON Schema

| Field      | Type            | Required | Description                                                                                                           |
| ---------- | --------------- | -------- | --------------------------------------------------------------------------------------------------------------------- |
| `sections` | object          | yes      | Section data map — same format as page JSON sections                                                                  |
| `order`    | string[]        | yes      | Section render order                                                                                                  |
| `layout`   | string \| false | no       | Layout type (e.g. `"page"`, `"full-width"`). Defaults to `"page"`. Pass `false` to render without header/footer zones |
| `wrapper`  | string          | no       | CSS-selector string that wraps all sections in an HTML element                                                        |

## Assigning a Template to a Page

Set the `template` column on the `Page` model:

```php
$page = Page::find(1);
$page->template = 'page.alternate';
$page->save();
```

Or when creating a page:

```php
Page::create([
    'title'    => 'About Us',
    'slug'     => 'about',
    'template' => 'page.alternate',
    'content'  => '<p>About our company.</p>',
]);
```

Template names map to filenames without the `.json` extension:

| `template` field   | File loaded                     |
| ------------------ | ------------------------------- |
| `null` or `""`     | `templates/page.json`           |
| `"page"`           | `templates/page.json`           |
| `"page.alternate"` | `templates/page.alternate.json` |
| `"product"`        | `templates/product.json`        |

If the selected template file does not exist, the package falls back to `page.json`. If `page.json` also does not exist, a 404 is returned.

## The `wrapper` Property

The `wrapper` field wraps all rendered section HTML in a single HTML element. The value uses a CSS-selector-like syntax:

```
tag#id.class1.class2[attr1=val1][attr2=val2]
```

Supported wrapper tags: `<div>`, `<main>`, `<section>`.

```json
{
  "wrapper": "div#div_id.div_class[attribute-one=value]",
  "sections": { "main": { "type": "page-content" } },
  "order": ["main"]
}
```

Output:

```html
<div id="div_id" class="div_class" attribute-one="value">
  <!-- rendered page sections -->
</div>
```

::: v-pre

## Variable Interpolation

Template section settings support `{{ $page->attribute }}` placeholders. At render time they are replaced with the corresponding attribute from the `Page` Eloquent model.

```json
{
  "sections": {
    "hero": {
      "type": "hero",
      "settings": {
        "title": "{{ $page->title }}",
        "description": "{{ $page->meta_description }}"
      }
    },
    "main": { "type": "page-content" }
  },
  "order": ["hero", "main"]
}
```

:::

Any column on the `Page` model can be used: `title`, `slug`, `content`, `meta_title`, `meta_description`, `meta_keywords`, or any custom column. Missing or `null` attributes resolve to an empty string.

## Alternative Template Example

```json
// resources/views/templates/page.alternate.json
{
  "wrapper": "main#page-alternate.page-wrapper",
  "sections": {
    "main": {
      "type": "page-content"
    }
  },
  "order": ["main"]
}
```

## `layout: false` — Rendering Without Header/Footer

Set `"layout": false` to skip the layout zone system entirely. No `@sections('header')` or `@sections('footer')` zones are rendered:

```json
{
  "layout": false,
  "sections": {
    "main": { "type": "hero" }
  },
  "order": ["main"]
}
```

## Theme-Aware Templates

If a theme is active, `TemplateStorage` checks the theme's `views/templates/` directory first. This allows themes to override the default `page.json` template or add new template files without touching the application's templates directory:

```
themes/my-theme/views/templates/page.json         ← overrides app templates/page.json
themes/my-theme/views/templates/product.json      ← theme-specific product template
```

## Template with Sections

```json
{
  "sections": {
    "header": {
      "type": "site-header",
      "settings": { "sticky": true },
      "blocks": {},
      "order": [],
      "disabled": false
    },
    "hero": {
      "type": "hero",
      "settings": { "title": "Welcome" },
      "blocks": {},
      "order": [],
      "disabled": false
    },
    "content": {
      "type": "page-content",
      "settings": {},
      "blocks": {},
      "order": [],
      "disabled": false
    },
    "footer": {
      "type": "site-footer",
      "settings": {},
      "blocks": {},
      "order": [],
      "disabled": false
    }
  },
  "order": ["header", "hero", "content", "footer"]
}
```

## Tips

1. **Use templates wisely** — Don't create too many templates
2. **Keep templates simple** — Templates should be minimal fallback layouts
3. **Test fallbacks** — Ensure pages work without custom JSON
4. **Use variable interpolation** — Make templates dynamic with page attributes
5. **Theme overrides** — Use theme templates for design-specific layouts
