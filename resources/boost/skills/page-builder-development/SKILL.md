---
name: page-builder-development
description: Laravel Page Builder development. Activate when creating or modifying sections, blocks, themes, page JSON layouts, schema definitions, registry classes, rendering pipeline, editor integration, or any feature of the coderstm/laravel-page-builder package.
---

# Laravel Page Builder Development

The Page Builder is a multi-theme, JSON-driven page composition system for Laravel inspired by Shopify's theme architecture. Pages are assembled from sections and blocks, driven by a Page JSON document.

## Documentation

Use `search-docs` for detailed Page Builder patterns and documentation.

## Architecture

Five layers — dependencies flow downward only:

- **Schema** (`src/Schema/`) — Immutable `SectionSchema`, `BlockSchema`, `SettingSchema` value objects
- **Registry** (`src/Registry/`) — `SectionRegistry`, `BlockRegistry` discover schemas from Blade `@schema()` directives
- **Components** (`src/Components/`, `src/Collections/`) — Runtime `Section`, `Block`, `Settings`, `BlockCollection`, `SectionCollection`
- **Rendering** (`src/Rendering/`) — `Renderer` hydrates JSON → objects and renders via Blade
- **Services** (`src/Services/`) — `PageRenderer`, `PageStorage`, `PageRegistry`, `ThemeSettings`

> Use `search-docs` for layer boundaries and dependency injection patterns.

## Usage

- **Facades**: `Section::get('hero')`, `Block::get('row')`, `Page::findBySlug('home')`, `Theme::active()`
- **Config**: See `config/pagebuilder.php` for `pages`, `sections`, `blocks`, `disk`, and `theme_settings_schema`
- **Helpers**: `pb_editor()` — bool editor mode; `theme()` — active theme name; `theme_vite()` — Vite loader
- **Artisan**: `php artisan pages:regenerate` to rebuild the page registry cache
- **Routes**: All editor API endpoints are under `/pagebuilder/*`; published pages are served at `/{slug}`

## Blade Directives

- `@schema([...])` — Schema definition; no-op at render time, extracted at registration
- `@blocks($section)` — Renders all top-level blocks of a section
- `@blocks($block)` — Renders all child blocks of a container block
- `@sections('header')` — Renders a layout slot (header/footer)
- `@pbEditorClass` — Adds `js pb-design-mode` to `<html>` in editor mode

> Use `search-docs` for directive usage and rendering pipeline details.

## Setup Workflows

### Create a New Section

```
- [ ] Create resources/views/sections/{type}.blade.php
- [ ] Add @schema([...]) with name, settings, blocks, presets
- [ ] Add {!! $section->editorAttributes() !!} to the root HTML element
- [ ] Use $section->settings->key for setting values
- [ ] Use @blocks($section) to render child blocks
- [ ] Section is auto-discovered by SectionRegistry on next request
```

> Use `search-docs` for section setting types and blocks array options.

### Create a New Block

```
- [ ] Create resources/views/blocks/{type}.blade.php
- [ ] Add @schema([...]) with name, settings, and blocks (if container)
- [ ] Add {!! $block->editorAttributes() !!} to the root HTML element
- [ ] Use $block->settings->key for setting values
- [ ] Use @blocks($block) if block is a container (e.g. row, accordion)
- [ ] Block is auto-discovered by BlockRegistry on next request
```

> Use `search-docs` for nested block patterns and theme vs local block resolution.

### Create a Page JSON Layout

```
- [ ] Create resources/views/pages/{slug}.json
- [ ] Define sections map keyed by unique IDs
- [ ] Set type to a registered section type
- [ ] Add settings, blocks, and order arrays
- [ ] Add top-level order array defining section render sequence
- [ ] Optionally add layout key for header/footer slot overrides
```

> Use `search-docs` for the full Page JSON field reference and nesting rules.

### Build a Theme

```
- [ ] Create themes/{name}/views/layouts/app.blade.php
- [ ] Add @pbEditorClass to <html>, @sections('header'), @yield('content'), @sections('footer')
- [ ] Create theme sections in themes/{name}/views/sections/
- [ ] Create theme blocks in themes/{name}/views/blocks/
- [ ] Register theme in config/themer.php
- [ ] Define theme_settings_schema in config/pagebuilder.php if needed
- [ ] Run php artisan theme:link to symlink public assets
```

> Use `search-docs` for theme shadowing rules and asset loading patterns.

### Add Layout Sections (Header / Footer)

```
- [ ] Add layout key to page JSON with sections map keyed by position slug
- [ ] Use 'header' key for top zone, 'footer' (or any other key) for bottom zone
- [ ] Add @sections('header') and @sections('footer') to the Blade layout file
- [ ] Set disabled: true to suppress a slot without removing it
```

## Best Practices

### Strict Typing

All PHP files must declare `declare(strict_types=1)`. All properties must be explicitly typed. All methods must have return types. Use `readonly` for value objects (Schema classes). Use PHP 8.2+ features: readonly properties, enums, named arguments, match expressions.

### Dependency Injection

Always inject services via constructor — never call `new ClassName()` directly. All core services are singletons registered by `PageBuilderServiceProvider`.

### Schema Immutability

Schema objects (`SectionSchema`, `BlockSchema`, `SettingSchema`) must never be mutated after construction. They are pure value objects and should be treated as immutable.

### Rendering Pipeline

All rendering must go through `Renderer`. Never call `view()->make()` directly for sections or blocks. In Blade templates, always use `@blocks()` — never call the renderer from a view.

### Theme Block Resolution

A bare `{ type: 'row' }` entry in a section's `blocks` array is a theme registry lookup. An entry with extra keys (`name`, `settings`, etc.) is a local definition used as-is. This allows sections to reference `row` without repeating its internal child-slot definition.

## Key API Endpoints

| Purpose                  | Method | Endpoint                      |
| ------------------------ | ------ | ----------------------------- |
| Load editor SPA          | GET    | `/pagebuilder/{slug?}`        |
| List all pages           | GET    | `/pagebuilder/pages`          |
| Get page JSON + layout   | GET    | `/pagebuilder/page/{slug}`    |
| Render section (preview) | POST   | `/pagebuilder/render-section` |
| Render block (preview)   | POST   | `/pagebuilder/render-block`   |
| Save page JSON           | POST   | `/pagebuilder/save-page`      |
| Get theme settings       | GET    | `/pagebuilder/theme-settings` |
| Save theme settings      | POST   | `/pagebuilder/theme-settings` |
| List assets              | GET    | `/pagebuilder/assets`         |
| Upload asset             | POST   | `/pagebuilder/assets/upload`  |
| Render published page    | GET    | `/{slug}`                     |
