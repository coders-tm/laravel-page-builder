---
title: Installation
---

# Installation

Install Laravel Page Builder using Composer.

## Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, or 13.x

## Install via Composer

```bash
composer require coderstm/laravel-page-builder
```

The package auto-registers its service provider via Laravel's package discovery.

## Run the Install Command

```bash
php artisan pagebuilder:install
```

This single command:

1. Publishes `config/pagebuilder.php`
2. Publishes frontend assets and static files
3. Scaffolds default starter views into your app:
   - `resources/views/layouts/page.blade.php` — base HTML layout
   - `resources/views/sections/` — announcement, header, hero, rich-text, content, page-content, footer
   - `resources/views/blocks/` — row, column, text
   - `resources/views/templates/` — page.json
4. Automatically runs the Page Builder database migrations

### Options

| Flag      | Description                        |
| --------- | ---------------------------------- |
| `--force` | Overwrite files that already exist |

```bash
# Overwrite existing files during installation
php artisan pagebuilder:install --force
```

## Publish the Config

The install command publishes the config automatically. If you need to re-publish it later:

```bash
php artisan vendor:publish --tag=pagebuilder-config
```

This creates `config/pagebuilder.php` in your application. See [Configuration](/configuration) for all available options.

## Verify Installation

1. Visit your application in the browser
2. Add `?editor=true` to any page URL
3. You should see the visual editor interface

```bash
# Example: Visit http://your-app.test?editor=true
```

The editor uses two query parameters:

- `?editor=true` — loads the editor frame (the React SPA)
- `?pb-editor=1` — activates editor mode (data attributes, CSS classes)
