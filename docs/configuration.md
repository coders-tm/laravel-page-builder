---
title: Configuration
---

# Configuration

Laravel Page Builder is configured through the `config/pagebuilder.php` file, published to your application during installation.

```bash
php artisan vendor:publish --tag=pagebuilder-config
```

---

## Editor & Routing Configuration

### prefix

- **Type:** `string`
- **Default:** `env('PAGEBUILDER_PREFIX', 'pagebuilder')`

Base URL path for the page builder editor interface and internal API routes (e.g. `/pagebuilder`).

```php
'prefix' => env('PAGEBUILDER_PREFIX', 'pagebuilder'),
```

### basePath

- **Type:** `string`
- **Default:** `env('PAGEBUILDER_BASE_PATH', '/')`

The root URL prefix for dynamic public pages rendered by the page builder (e.g. `/home`, `/about`).

```php
'basePath' => env('PAGEBUILDER_BASE_PATH', '/'),
```

### preserved_params

- **Type:** `array`
- **Default:** `[]`

Additional query parameters to preserve during editor navigation and frame reloads (e.g., `utm_source`, `ref`).

```php
'preserved_params' => [
    // 'ref',
    // 'utm_source',
],
```

---

## Storage Paths

### pages

- **Type:** `string`
- **Default:** `resource_path('views/pages')`

Path to the directory storing dynamic page JSON data files.

```php
'pages' => resource_path('views/pages'),
```

### sections

- **Type:** `string`
- **Default:** `resource_path('views/sections')`

Path to the directory storing section Blade templates (`.blade.php`).

```php
'sections' => resource_path('views/sections'),
```

### blocks

- **Type:** `string`
- **Default:** `resource_path('views/blocks')`

Path to the directory storing theme block Blade templates (`.blade.php`).

```php
'blocks' => resource_path('views/blocks'),
```

### templates

- **Type:** `string`
- **Default:** `resource_path('views/templates')`

Path to the directory storing page layout JSON template files.

```php
'templates' => resource_path('views/templates'),
```

---

## Middleware & Security

### middleware

- **Type:** `array`
- **Default:** `['web']`

Middleware pipeline applied to page builder editor and API routes. Add authentication middleware here to restrict builder access.

```php
'middleware' => [
    'web',
    // 'auth',
],
```

---

## Asset Storage

### disk

- **Type:** `string`
- **Default:** `env('PAGEBUILDER_DISK', 'public')`

Filesystem disk (configured in `config/filesystems.php`) used for uploaded media and images.

```php
'disk' => env('PAGEBUILDER_DISK', 'public'),
```

### asset_directory

- **Type:** `string`
- **Default:** `env('PAGEBUILDER_ASSET_DIRECTORY', 'pagebuilder')`

Directory path within the selected storage disk where uploaded files are stored.

```php
'asset_directory' => env('PAGEBUILDER_ASSET_DIRECTORY', 'pagebuilder'),
```

---

## Reserved Routes

### preserved_pages

- **Type:** `array`
- **Default:** `['home', 'admin', 'user', 'api', 'storage', 'uploads', 'files', 'vendor']`

Reserved URL slugs that cannot be assigned to dynamic pages created in the builder to prevent route collisions.

```php
'preserved_pages' => [
    'home',
    'admin',
    'user',
    'api',
    'storage',
    'uploads',
    'files',
    'vendor',
],
```

---

## Theme Settings

### theme_settings_path

- **Type:** `string`
- **Default:** `resource_path('settings.json')`

Path to the JSON file where global theme setting values are persisted.

```php
'theme_settings_path' => resource_path('settings.json'),
```

### theme_settings_schema

- **Type:** `array`
- **Default:** Defined in `config/pagebuilder.php` (Colors, Typography, Radius & Shape)

Schema definition for global design tokens editable via the builder side panel. Each item defines a group containing setting fields.

```php
'theme_settings_schema' => [
    [
        'name' => 'Colors',
        'settings' => [
            [
                'key'     => 'colors.primary',
                'label'   => 'Primary',
                'type'    => 'color',
                'mode'    => 'hsl',
                'default' => 'hsl(168 94% 7%)',
                'css_var' => '--color-primary',
            ],
        ],
    ],
    [
        'name' => 'Typography',
        'settings' => [
            [
                'key'     => 'fonts.body',
                'label'   => 'Body font',
                'type'    => 'google_font',
                'default' => 'Instrument Sans, Inter, sans-serif',
                'css_var' => '--font-body',
            ],
        ],
    ],
],
```

**Setting Schema Fields:**

| Field     | Required | Description                                                              |
| --------- | -------- | ------------------------------------------------------------------------ |
| `key`     | Yes      | Dot-notation key used for storage (e.g. `colors.primary`)                |
| `label`   | Yes      | Field label displayed in the editor sidebar                              |
| `type`    | Yes      | Field input type (`color`, `text`, `select`, `google_font`)              |
| `mode`    | No       | Color format mode if type is `color` (e.g. `hsl`)                        |
| `default` | Yes      | Fallback value when no custom value is saved                             |
| `css_var` | No       | CSS variable updated dynamically in the preview (e.g. `--color-primary`) |

---

## Environment Variables

The package configuration supports environment variable overrides for common settings:

```ini
PAGEBUILDER_PREFIX=pagebuilder
PAGEBUILDER_BASE_PATH=/
PAGEBUILDER_DISK=public
PAGEBUILDER_ASSET_DIRECTORY=pagebuilder
```

| Variable                      | Config Key        | Default Value   | Description                            |
| ----------------------------- | ----------------- | --------------- | -------------------------------------- |
| `PAGEBUILDER_PREFIX`          | `prefix`          | `'pagebuilder'` | Editor and API URL route prefix        |
| `PAGEBUILDER_BASE_PATH`       | `basePath`        | `'/'`           | Base URL path for dynamic public pages |
| `PAGEBUILDER_DISK`            | `disk`            | `'public'`      | Storage disk for uploaded media assets |
| `PAGEBUILDER_ASSET_DIRECTORY` | `asset_directory` | `'pagebuilder'` | Subfolder inside the disk for uploads  |
