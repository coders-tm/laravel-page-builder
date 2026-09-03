---
title: API Reference
---

# API Reference

Laravel Page Builder provides API endpoints for the visual editor. These endpoints are used by the React SPA to load, save, and render page content.

## Base URL

All API endpoints are prefixed with the configured `prefix` (default: `/pagebuilder`).

## Endpoints

### Get Page JSON

```
GET /pagebuilder/{slug}.json
```

Returns the JSON data for a page.

**Response:**

```json
{
  "title": "Home",
  "meta": {
    "description": "Welcome to our site"
  },
  "sections": {
    "hero-1": {
      "type": "hero",
      "settings": {
        "title": "Welcome",
        "subtitle": "Build amazing pages"
      },
      "blocks": {},
      "order": []
    }
  },
  "order": ["hero-1"]
}
```

### Save Page JSON

```
POST /pagebuilder/{slug}
```

Saves the JSON data for a page and persists meta to the database.

**Request:**

```json
{
  "slug": "home",
  "data": {
    "sections": {
      "hero-1": {
        "type": "hero",
        "settings": {
          "title": "Welcome",
          "subtitle": "Build amazing pages"
        },
        "blocks": {},
        "order": []
      }
    },
    "order": ["hero-1"]
  },
  "meta": {
    "title": "Home",
    "meta_title": "Home | My Site",
    "meta_description": "Welcome to our site"
  },
  "theme_settings": {
    "colors.primary": "#6366f1"
  }
}
```

| Field            | Required | Description                                     |
| ---------------- | -------- | ----------------------------------------------- |
| `slug`           | Yes      | Page slug (alphanumeric, dashes, slashes)       |
| `data`           | Yes      | Page JSON data (sections, order, layout)        |
| `meta`           | No       | Page meta (title, meta_title, meta_description) |
| `theme_settings` | No       | Theme settings key-value pairs                  |

**Response:**

```json
{
  "message": "Page has been saved successfully"
}
```

### Render Section

```
POST /pagebuilder/render-section
```

Live-renders a section with the provided settings.

**Request:**

```json
{
  "slug": "home",
  "section_id": "hero-1",
  "section_type": "hero",
  "settings": {
    "title": "Welcome",
    "subtitle": "Build amazing pages"
  },
  "blocks": {},
  "order": []
}
```

| Field          | Required | Description                                  |
| -------------- | -------- | -------------------------------------------- |
| `section_id`   | Yes      | Unique section instance ID                   |
| `section_type` | Yes      | Section type (matches Blade filename)        |
| `slug`         | No       | Page slug (for template variable resolution) |
| `settings`     | No       | Section settings object                      |
| `blocks`       | No       | Nested blocks data                           |
| `order`        | No       | Block render order                           |

**Response:**

```json
{
  "html": "<section data-section-id=\"hero-1\" data-editor-section='{...}'><h1>Welcome</h1><p>Build amazing pages</p></section>"
}
```

### Render Block

```
POST /pagebuilder/render-block
```

Live-renders a block with the provided settings.

**Request:**

```json
{
  "slug": "home",
  "type": "text",
  "settings": {
    "content": "Hello World"
  },
  "blocks": {},
  "order": []
}
```

| Field      | Required | Description                         |
| ---------- | -------- | ----------------------------------- |
| `type`     | Yes      | Block type (matches Blade filename) |
| `slug`     | No       | Page slug (for variable resolution) |
| `settings` | No       | Block settings object               |
| `blocks`   | No       | Nested child blocks data            |
| `order`    | No       | Child block render order            |

**Response:**

```json
{
  "html": "<div data-block-id=\"text-1\" data-editor-block='{...}'><p>Hello World</p></div>"
}
```

### Get Theme Settings

```
GET /pagebuilder/theme-settings
```

Returns the theme settings schema and current values.

**Response:**

```json
{
  "schema": [
    {
      "name": "Colors",
      "settings": [
        {
          "key": "colors.primary",
          "label": "Primary",
          "type": "color",
          "default": "#6366f1",
          "css_var": "--color-primary"
        }
      ]
    }
  ],
  "values": {
    "colors.primary": "#6366f1"
  }
}
```

### Save Theme Settings

```
POST /pagebuilder/theme-settings
```

Saves the theme settings values.

**Request:**

```json
{
  "values": {
    "colors.primary": "#6366f1",
    "colors.secondary": "#4f46e5"
  }
}
```

| Field    | Required | Description                       |
| -------- | -------- | --------------------------------- |
| `values` | Yes      | Key-value pairs of setting values |

**Response:**

```json
{
  "message": "Theme settings have been saved successfully"
}
```

### List Assets

```
GET /pagebuilder/assets
```

Returns a paginated list of uploaded assets.

**Query Parameters:**

| Parameter | Type   | Description              |
| --------- | ------ | ------------------------ |
| `page`    | int    | Page number (default: 1) |
| `q`       | string | Search query             |

**Response:**

```json
{
  "data": [
    {
      "id": "abc123",
      "name": "image.jpg",
      "url": "/storage/pagebuilder/image.jpg",
      "thumbnail": "/storage/pagebuilder/image.jpg",
      "size": 123456,
      "type": "image/jpeg"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

### Upload Asset

```
POST /pagebuilder/assets/upload
```

Uploads a new asset.

**Request:**

```
Content-Type: multipart/form-data

file: [binary data]
```

**Response:**

```json
{
  "id": "abc123",
  "name": "image.jpg",
  "url": "/storage/pagebuilder/image.jpg",
  "thumbnail": "/storage/pagebuilder/image.jpg",
  "size": 123456,
  "type": "image/jpeg"
}
```

## Authentication

### Middleware

By default, editor routes use the `web` middleware. Add authentication middleware for protected editors:

```php
// config/pagebuilder.php
'middleware' => ['web', 'auth'],
```

### Authorization Callback

Register a custom authorization callback for more granular control:

```php
use PageBuilder\PageBuilder;

// In AppServiceProvider or dedicated service provider
public function boot()
{
    PageBuilder::auth(function ($request) {
        // Return true if the user is authorized to access the editor
        return auth()->check() && auth()->user()->is_admin;
    });
}
```

## Frontend Integration

### Editor Initialization

```html
<script src="/vendor/pagebuilder/app.js"></script>
<script>
  PageBuilder.init({
    baseUrl: "/pagebuilder",
    assets: {
      provider: myAssetProvider,
    },
  })
</script>
```

### Custom Asset Provider

```js
const myAssetProvider = {
  async list({ page = 1, search = "" } = {}) {
    const q = new URLSearchParams({ page, q: search })
    const res = await fetch(`/api/pagebuilder/assets?${q}`)
    if (!res.ok) throw new Error("Failed to fetch assets")
    return res.json()
  },
  async upload(file) {
    const body = new FormData()
    body.append("file", file)
    const res = await fetch("/api/pagebuilder/assets/upload", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN":
          document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "",
      },
      body,
    })
    if (!res.ok) throw new Error("Upload failed")
    return res.json()
  },
}
```

## Error Handling

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Error details"]
  }
}
```

### HTTP Status Codes

| Code | Description      |
| ---- | ---------------- |
| 200  | Success          |
| 400  | Bad Request      |
| 401  | Unauthorized     |
| 403  | Forbidden        |
| 404  | Not Found        |
| 422  | Validation Error |
| 500  | Server Error     |

## Tips

1. **Use HTTPS** — Always use HTTPS in production
2. **CSRF Protection** — Include CSRF token in requests
3. **Rate Limiting** — Implement rate limiting for uploads
4. **File Validation** — Validate file types and sizes
5. **Error Handling** — Handle errors gracefully in the frontend
