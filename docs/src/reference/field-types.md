---
title: Field Types
---

# Field Types Reference

This page provides a comprehensive list of all available input and content settings for sections and blocks.

## Standard Attributes

All input settings share these core attributes:

| Attribute     | Type   | Description                                             |
| ------------- | ------ | ------------------------------------------------------- |
| `type`        | string | **Required.** The input type identifier.                |
| `id`          | string | **Required.** Unique key used to access value in Blade. |
| `label`       | string | Display label shown in the editor.                      |
| `default`     | mixed  | Default value.                                          |
| `info`        | string | Helper text displayed below the field.                  |
| `placeholder` | string | Placeholder text (supported by most text inputs).       |

---

## Basic Input Settings

### `text`

Single-line text input. Returns a string.

```json
{ "type": "text", "id": "heading", "label": "Heading", "default": "Hello" }
```

### `textarea`

Multi-line text input. Returns a string.

```json
{ "type": "textarea", "id": "content", "label": "Body Text" }
```

### `number`

Numeric input. Returns a number.

```json
{ "type": "number", "id": "columns", "label": "Columns", "default": 3 }
```

### `checkbox`

Boolean toggle. Returns `true` or `false`.

```json
{
  "type": "checkbox",
  "id": "show_title",
  "label": "Show title",
  "default": true
}
```

### `radio` / `select`

Options selection. Requires an `options` array.

```json
{
  "type": "select",
  "id": "position",
  "label": "Position",
  "options": [
    { "value": "left", "label": "Left" },
    { "value": "center", "label": "Center" }
  ]
}
```

### `range`

Slider input. Requires `min`, `max`, and `step`.

```json
{
  "type": "range",
  "id": "font_size",
  "label": "Font size",
  "min": 12,
  "max": 48,
  "step": 2,
  "unit": "px"
}
```

---

## Specialized Input Settings

### `color`

Hex color picker. Returns a string (e.g., `#ffffff`).

```json
{
  "type": "color",
  "id": "bg_color",
  "label": "Background Color",
  "default": "#6366f1"
}
```

### `color_background`

CSS background picker with gradient support.

```json
{
  "type": "color_background",
  "id": "background",
  "label": "Background",
  "default": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
}
```

### `url`

URL input with validation.

```json
{
  "type": "url",
  "id": "link",
  "label": "Link URL",
  "default": "https://example.com"
}
```

### `image_picker`

Media library selector. Returns an image URL.

```json
{
  "type": "image_picker",
  "id": "hero_image",
  "label": "Hero Image"
}
```

## Icon Pickers

### `icon_fa`

FontAwesome icon picker.

```json
{
  "type": "icon_fa",
  "id": "icon",
  "label": "Icon",
  "default": "fas fa-star"
}
```

### `icon_md`

Material Design icon picker.

```json
{
  "type": "icon_md",
  "id": "icon",
  "label": "Icon",
  "default": "star"
}
```

---

## Content Settings

### `richtext`

Rich text editor (multi-line).

```json
{
  "type": "richtext",
  "id": "content",
  "label": "Content"
}
```

### `inline_richtext`

Rich text editor (single-line).

```json
{
  "type": "inline_richtext",
  "id": "title",
  "label": "Title",
  "default": "Hello World"
}
```

### `text_alignment`

Left/Center/Right segmented control.

```json
{
  "type": "text_alignment",
  "id": "alignment",
  "label": "Alignment",
  "default": "center"
}
```

### `html`

Raw HTML code editor.

```json
{
  "type": "html",
  "id": "custom_html",
  "label": "Custom HTML"
}
```

### `blade`

Blade template code editor.

```json
{
  "type": "blade",
  "id": "custom_blade",
  "label": "Custom Blade Template"
}
```

---

## Layout Settings

### `header`

Sidebar section divider.

```json
{
  "type": "header",
  "content": "Section Title"
}
```

### `paragraph`

Sidebar informational text.

```json
{
  "type": "paragraph",
  "content": "This is a helper text."
}
```

---

## Advanced Settings

### `external`

Dynamic API-driven selector.

```json
{
  "type": "external",
  "id": "post",
  "label": "Select Post",
  "endpoint": "/api/posts"
}
```

### `google_font`

Google Font selector.

```json
{
  "type": "google_font",
  "id": "font_family",
  "label": "Font Family",
  "default": "Inter"
}
```

---

## Using Settings in Blade

### Basic Access

```blade
<h1>{{ $section->settings->title }}</h1>
<p>{{ $section->settings->content }}</p>
```

### Conditional Rendering

```blade
@if($section->settings->show_subtitle)
    <p>{{ $section->settings->subtitle }}</p>
@endif
```

### Dynamic Classes

```blade
<div class="text-{{ $section->settings->alignment }}">
    Content
</div>
```

### Inline Styles

```blade
<div style="background-color: {{ $section->settings->bg_color }}">
    Content
</div>
```

### Images

```blade
<img src="{{ $section->settings->hero_image }}" alt="Hero">
```

### Icons

```blade
<i class="{{ $section->settings->icon }}"></i>
```

### Rich Text

```blade
<div class="prose">
    {!! $section->settings->content !!}
</div>
```

---

## Tips

1. **Use appropriate types** — Choose the right type for each setting
2. **Set defaults** — Always provide sensible defaults
3. **Use info text** — Help users understand what each setting does
4. **Validate input** — Use URL/image types for validation
5. **Test in editor** — Verify settings work in the visual editor
