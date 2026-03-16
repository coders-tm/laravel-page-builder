# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - 2026-03-16

### Added

- `PageBuilder::usePageModel()` method for custom page model support
- Template fallback support in `PageService` for rendering pages when JSON is missing
- Automatic sharing of `$page` model with all views rendered via `PageService`

### Changed

- Enhanced page meta resolution priority in `PageService` (Arg → DB → JSON)

### Refactored

- Renamed master layout file from `app.blade.php` to `page.blade.php`

## [1.0.8] - 2026-03-16

### Added

- `pagebuilder:install` Artisan command for automated setup and resource publishing
- `Page Content` section stub with customizable padding and max-width options
- Default theme setting in `WorkbenchServiceProvider` for easier development

### Changed

- Comprehensive update to `README.md` with detailed installation, configuration, and usage guides

## [1.0.7] - 2026-03-15

### Added

- Implemented a new Tailwind CSS design system with custom colors and fonts for core layouts, text blocks, and section components
- Added new announcement section and refactored layout handling to support dynamic layout types
- Implemented iframe communication for interactive preview
- Added support for recursive nested block operations and schema adjustments
- Enhanced banner section layout to include heading and image
- Implemented block limit functionality to disable add block options when limits are reached
- Added `local` flag to `BlockSchema` for handling local block previews in `AddBlockModal`
- Enhanced `LayoutPanel` and `LayoutSectionRow` components with improved block selection and management features

### Changed

- Refactored drag-and-drop functionality in `LayoutPanel` to consolidate `DndContext` and improve section reordering

## [1.0.6] - 2026-03-15

### Changed

- Enhanced block selection logic in `PreviewCanvas` to handle empty paths and fallback to section selection

## [1.0.5] - 2026-03-15

### Changed

- Adjusted `scriptVariables` method to reorder pages array for improved structure

## [1.0.4] - 2026-03-15

### Added

- Enhanced home page structure with detailed contact form and social blocks
- Added contact form section with schema and blocks
- Added `SESSION_DRIVER` configuration to testbench environment

### Changed

- Refactored page routes registration

## [1.0.3] - 2026-03-14

### Added

- `ThemeMiddleware` — automatically apply themes via route parameters or session
- Page meta persistence — title and meta data (description, keywords) are now saved to the JSON file for preserved slugs (e.g., home)
- Live text setting detection — intelligent detection of text-based settings for conditional updates in the editor
- `preserved_pages` configuration — reserve specific slugs that cannot be used for dynamic pages
- Slug validation — dynamic pages now prevent using reserved slugs (e.g., 'home') via model-level validation
- Automatic home route — `PageService` now automatically registers the root `/` route for the 'home' preserved slug

### Changed

- `PageStorage` now strip DB-only fields contextually based on whether the page is preserved or dynamic
- `PageBuilderController` merges database-stored metadata into the editor response
- Renamed `Page::findBySlug` to `Page::findActiveBySlug`
- `PageData` now supports and hydrates `meta` fields
- Optimized `dist/` assets with latest build

## [1.0.2] - 2026-03-14

### Added

- Enhanced `composer.json` with dev-dependencies and IDE support
- Improved test coverage for theme application

## [1.0.1] - 2026-03-14

### Fixed

- Add null checks for section and block configurations in `PageBuilderServiceProvider`

## [1.0.0] - 2026-03-14

### Added

- Blade-native rendering — sections and blocks are regular Blade views with typed PHP objects
- `@schema()` directive — declare settings, child blocks, and presets directly in Blade templates
- Visual editor — React SPA with iframe live preview, drag-and-drop, and inline text editing
- JSON-based storage — page data stored as JSON files on disk for fast reads and easy version control
- Per-page layouts — header and footer configurable per-page via the `layout` key in page JSON
- Recursive block nesting — container blocks (rows, columns) can hold child blocks to any depth
- Theme blocks — register global block types that any section can accept via `@theme` wildcard
- 21+ field types: `text`, `textarea`, `richtext`, `inline_richtext`, `select`, `radio`, `checkbox`, `range`, `number`, `color`, `color_background`, `image_picker`, `url`, `video_url`, `icon_fa`, `icon_md`, `text_alignment`, `html`, `blade`, `header`, `paragraph`, `external`
- Editor mode — `data-editor-*` attributes injected only when the editor is active
- `@blocks()` directive — renders top-level section blocks or nested child blocks of a container block
- `@sections()` directive — renders layout slot sections (header/footer) from the Blade layout file
- `@pbEditorClass` directive — outputs CSS class when editor mode is active
- `pb_editor()` global helper — returns `true` when the editor is active
- Built-in `section`, `row`, and `column` Blade views with Tailwind CSS
- `SectionRegistry` and `BlockRegistry` with support for additional discovery paths via `Section::add()` / `Block::add()`
- Manual schema registration via `Section::register()` / `Block::register()`
- `PageRenderer` service — loads page JSON, renders all enabled sections in order
- `PagePublisher` — compiles pages into static Blade files
- `PageRegistry` — cached page manifest at `bootstrap/cache/pagebuilder_pages.php`
- Custom asset provider system — swap the default Laravel disk for S3, Cloudflare R2, Cloudinary, or any custom backend
- Multi-theme support via `qirolab/laravel-themer` integration
- Global theme settings schema (`theme_settings_schema`) with `$theme` variable shared to all Blade views
- Editor API endpoints: list pages, get page JSON, live-render a section, save page, manage assets
- `pages:regenerate` Artisan command — regenerates the page registry cache
- `theme:link` Artisan command — symlinks theme assets into the public directory
- Publishable config, views, migrations, and frontend assets via `vendor:publish`
- PHP 8.2+ with strict typing, readonly properties, and PSR-12 compliance
- Laravel 11.x and 12.x support
