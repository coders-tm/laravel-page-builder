---
title: Configuration
---

# Configuration

Laravel Page Builder is configured through the `config/pagebuilder.php` file, published to your application during installation.

### prefix

- **Type:** `string`
- **Default:** `'pagebuilder'`

Base URL path for the page builder routes.

```php
'prefix' => 'pagebuilder',   // Editor at /pagebuilder
'prefix' => 'admin/builder', // Editor at /admin/builder
```

### basePath

- **Type:** `string`
- **Default:** `'/'`

The prefix for public pages and editor.

```php
'basePath' => '/',    // Pages at /home, /about
'basePath' => 'site', // Pages at /site/home, /site/about
```

### preserved_params

- **Type:** `array`
- **Default:** `[]`

Additional query parameters to preserve during editor navigation.

```php
'preserved_params' => ['ref', 'utm_source', 'utm_medium'],
```

### pages

- **Type:** `string`
- **Default:** `resource_path('views/pages')`

Path to page JSON data files.

```php
'pages' => resource_path('views/pages'),
'pages' => storage_path('app/pagebuilder/pages'),
```

### sections

- **Type:** `string`
- **Default:** `resource_path('views/sections')`

Path to section Blade templates.

```php
'sections' => resource_path('views/sections'),
'sections' => resource_path('views/pagebuilder/sections'),
```

### blocks

- **Type:** `string`
- **Default:** `resource_path('views/blocks')`

Path to theme block Blade templates.

```php
'blocks' => resource_path('views/blocks'),
'blocks' => resource_path('views/pagebuilder/blocks'),
```

### templates

- **Type:** `string`
- **Default:** `resource_path('views/templates')`

Path to JSON template files.

```php
'templates' => resource_path('views/templates'),
'templates' => resource_path('views/pagebuilder/templates'),
```

### middleware

- **Type:** `array`
- **Default:** `['web']`

Middleware applied to editor routes.

```php
'middleware' => ['web'],                    // Public editor
'middleware' => ['web', 'auth'],            // Authenticated users only
'middleware' => ['web', 'auth', 'admin'],   // Admin users only
```

### disk

- **Type:** `string`
- **Default:** `'public'`

Filesystem disk for asset uploads.

```php
'disk' => 'public',   // Local storage
'disk' => 's3',       // AWS S3
'disk' => 'r2',       // Cloudflare R2
```

### asset_directory

- **Type:** `string`
- **Default:** `'pagebuilder'`

Directory within the disk for uploaded assets.

```php
'asset_directory' => 'pagebuilder',
'asset_directory' => 'uploads/pagebuilder',
```

### preserved_pages

- **Type:** `array`
- **Default:** `['home', 'admin', 'user', 'api', 'storage', 'uploads', 'files', 'vendor']`

Reserved slugs that cannot be used for dynamic pages.

```php
'preserved_pages' => ['home', 'admin', 'api'],
```

### theme_settings_path

- **Type:** `string`
- **Default:** `resource_path('settings.json')`

Path to the JSON file storing theme setting values.

```php
'theme_settings_path' => resource_path('settings.json'),
'theme_settings_path' => storage_path('app/pagebuilder/settings.json'),
```

### theme_settings_schema

- **Type:** `array`
- **Default:** _(colors, typography, and radius settings — see below)`

Schema definition for global theme settings editable from the editor. Each entry is a group with a name and an array of setting definitions.

```php
'theme_settings_schema' => [
    [
        'name' => 'Colors',
        'settings' => [
            [
                'key'     => 'colors.primary',
                'label'   => 'Primary',
                'type'    => 'color',
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
                'default' => 'Inter, sans-serif',
                'css_var' => '--font-body',
            ],
        ],
    ],
],
```

**Setting fields:**

| Field     | Required | Description                                                              |
| --------- | -------- | ------------------------------------------------------------------------ |
| `key`     | Yes      | Dot-notation key for storage (`colors.primary`)                          |
| `type`    | Yes      | Field type: `color`, `text`, `select`, `google_font`                     |
| `label`   | Yes      | Human-readable label shown in the editor panel                           |
| `default` | Yes      | Fallback value when no override has been saved                           |
| `css_var` | No       | CSS custom property updated live in the preview (e.g. `--color-primary`) |

## Environment Variables

You can use environment variables to customize configuration per environment:

```env
PAGEBUILDER_PREFIX=pagebuilder
PAGEBUILDER_BASE_PATH=/
PAGEBUILDER_DISK=public
PAGEBUILDER_ASSET_DIRECTORY=pagebuilder
```

| Variable                      | Config Key        | Default       |
| ----------------------------- | ----------------- | ------------- |
| `PAGEBUILDER_PREFIX`          | `prefix`          | `pagebuilder` |
| `PAGEBUILDER_BASE_PATH`       | `basePath`        | `/`           |
| `PAGEBUILDER_DISK`            | `disk`            | `public`      |
| `PAGEBUILDER_ASSET_DIRECTORY` | `asset_directory` | `pagebuilder` |
