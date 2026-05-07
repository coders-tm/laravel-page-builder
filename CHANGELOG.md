# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.10] - 2026-05-07

### Changed

- **Nested Theme Settings** — Theme settings are now persisted under a `pagebuilder` key in the configured JSON file (`settings.json`). This allows theme settings to coexist with other application settings in the same file without conflict.
- **Strict Settings Loading** — Removed backward compatibility for the legacy flat JSON structure. Settings must now reside under the `pagebuilder` key to be loaded.
- **Updated Configuration** — Renamed default theme settings path to `resource_path('settings.json')` in `config/pagebuilder.php`.

### Fixed

- **Other Settings Preservation** — The `ThemeSettings` service now preserves any other top-level keys in the JSON file when saving theme settings.

## [1.3.9] - 2026-05-07

### Added

- **Blade view-based caching** — Replaced the custom `PageCache` service with a native Laravel approach. Rendered pages are now persisted as `.blade.php` files in the `resources/views/pages` directory, leveraging Laravel's built-in view compiler for performance and automatic invalidation.
- **PageViewCacheTest** — Added a comprehensive test suite for the new view-based caching lifecycle.

### Changed

- **Refactored caching logic** — Migrated `PageRenderer`, `PageService`, `Theme`, and `ThemeSettings` to use the new `PageStorage` based view caching flow.
- **Simplified configuration** — Removed legacy `ttl` and `prefix` settings from `config/pagebuilder.php`.

### Removed

- `PageCache` service and its associated generation-based invalidation logic.
- `PageCacheTest` and legacy unit tests for the removed service.

## [1.3.8] - 2026-05-07

### Added

- **Editor Authorization Hook** — Added `PageBuilder::auth()` to allow projects to register custom authorization callbacks for guarding the editor frame access. Unauthorized requests are automatically downgraded to regular page rendering.
- **Improved Test Reliability** — Added `RefreshDatabase` to `PageEditorFrameTest` to prevent "no such table" errors during full test suite runs.

## [1.3.7] - 2026-05-07

### Added

- **Nested slug support in saving** — The `POST {slug}` route now uses a `.*` constraint, enabling the editor to save data for nested pages (e.g., `parent/child`).

### Fixed

- **Editor root path resolution** — Visiting the editor at the root path (`/`) now correctly maps to the `home` slug instead of redirecting to `/home`. This ensures the editor loads the home page data while maintaining the root URL.
- **Navigation manager home mapping** — Navigating to the "home" page within the editor now correctly points back to the root path (`/`) instead of `/home`.
- **Query parameter filtering on exit** — Updated the `pagebuilder:exit` logic in `layout.blade.php` to filter query parameters based on the `preserved_params` configuration in `config/pagebuilder.php`, ensuring only explicitly allowed parameters persist after leaving the editor.

## [1.3.1] - 2026-04-16

### Added

- **Custom `basePath` configuration** — Set `basePath` in `config/pagebuilder.php` to customize the editor and API route prefix (e.g., `'basePath' => 'foo'` makes the editor accessible at `/foo` instead of `/pagebuilder`). Defaults to `'pagebuilder'` for backward compatibility.
  - All routes now dynamically use the configured `basePath`.
  - Frontend automatically derives `baseUrl` from the `basePath` via `PageBuilder::scriptVariables()`.
  - React Router `basename` is automatically extracted from the dynamic `baseUrl`, enabling the editor to work at any custom path without additional configuration.

### Changed

- **Route generation is now dynamic** — `routes/web.php` now reads `config('pagebuilder.basePath')` instead of using hardcoded paths, allowing deployment-time or environment-specific customization of the editor URL.

## [1.3.0] - 2026-04-15

### Added

- **Editor `mode` config option** — Set `mode: 'page'` (default) or `mode: 'email'` when calling `PageBuilder.init()`. Email mode automatically hides the page selector dropdown in the header and the sidebar tab strip (Sections / Page / Theme), making the editor suitable for email template and single-document editing contexts.
- **`BootstrapManager` now hydrates pages from config** — `config.pages` passed via `PageBuilder.init()` is now correctly synced into the Zustand store during bootstrap, fixing an issue where the page selector dropdown was always empty because the store was created before `setConfig()` was called.

### Changed

- **Unified API URL pattern** — Page load and save now use `baseUrl` exclusively, eliminating the need for separate endpoint configuration:
  - Load: `GET {baseUrl}/{slug}.json`
  - Save: `POST {baseUrl}/{slug}`
- **Laravel routes updated** to match the new URL pattern:
  - `GET pagebuilder/{slug}.json` → `PageBuilderController@page`
  - `POST pagebuilder/{slug}` → `PageBuilderController@savePage`
  - Removed the old `GET pagebuilder/page/{slug}` and `POST pagebuilder/save-page` routes.
- **Removed `GET /pagebuilder/pages` route and `pages()` controller method** — The page list is now passed directly via `PageBuilder::scriptVariables()` in the Blade layout config, removing an unnecessary API round-trip on editor boot.
- **`PageBuilder::scriptVariables()` pages formatting** — Pages are now mapped from `PageRegistry` using the pre-computed `path` field (which already includes the parent prefix), producing a clean `{ id, slug, title, parent }` shape for the frontend.

### Removed

- `api.getPages()` frontend method — no longer needed; pages are initialised from the server-rendered config.
- `PageSlice.loadPages()` store action and `PageManager.loadAll()` / `BootstrapManager.loadAll()` — replaced by config hydration at bootstrap time.
- `PageRegistry` dependency from `PageBuilderController` constructor — only used by the now-removed `pages()` method.

## [1.2.8] - 2026-03-31

### Changed

- `TemplateStorage` now safely resolves theme template paths and falls back to the configured templates directory. Scans only `.json` files and filters results by filename.

## [1.2.7] - 2026-03-31

### Added

- Automatic theme asset versioning by appending file modification timestamps as query parameters to URLs generated by the `theme()` helper to prevent browser caching issues.

## [1.2.6] - 2026-03-30

### Added

- Event listeners in the editor iframe for `pagebuilder:exit` and `pagebuilder:page-change` to facilitate seamless parent application integration.

## [1.2.5] - 2026-03-30

### Fixed

- `SelectField` component correctly filters out empty options.

### Refactored

- Core codebase for improved readability, maintainability, and consistent formatting across services and components.

## [1.2.4] - 2026-03-29

### Added

- "Exit" button in the `EditorHeader` component and dispatching of the `pagebuilder:exit` event.

### Changed

- Standardized `composer.json` indentation and formatting.

## [1.2.3] - 2026-03-29

### Fixed

- Nested page saving and loading by using full-path identifiers (e.g., `parent/slug`) consistently across services and controllers
- Database meta resolution and persistence for pages with parents in `PageService`
- Public route registration for nested pages to ensure correct JSON data is loaded during rendering
- Enhanced `PageCache` key generation by replacing slashes with dots to ensure compatibility with all cache drivers (especially `file` driver)

### Changed

- Updated `PageRegistry` to key active pages by their full path to prevent collisions between pages sharing the same slug under different parents

## [1.2.2] - 2026-03-29

### Fixed

- `RichTextField` content synchronization when switching between different blocks in the sidebar
- Page Builder Preview iframe synchronization to correctly reflect section order immediately after reordering
- `Select` component dropdown scrolling and viewport handling for long lists

### Refactored

- `Select` component styling and formatting for improved visual consistency

## [1.2.1] - 2026-03-28

### Changed

- Enhanced slug handling in page retrieval by extracting only the last segment from nested paths for more robust resolution

## [1.2.0] - 2026-03-27

### Enhanced

- General functional improvements and performance optimizations across the editor core

## [1.1.10] - 2026-03-26

### Fixed

- Enhanced slug handling in route registration and validation for nested slugs

## [1.1.9] - 2026-03-25

### Added

- `pageCacheKey` support for more granular cache management in `PageBuilder` and `PageRegistry`

## [1.1.8] - 2026-03-24

### Removed

- `EditorLogo` component functionality to simplify the editor UI

## [1.1.7] - 2026-03-23

### Added

- Resource registration methods and conditional route loading to `PageBuilder` for better extensibility

## [1.1.6] - 2026-03-22

### Added

- Integration of `RequestThemeMiddleware` into standard page routes for improved multi-theme support

## [1.1.5] - 2026-03-21

### Changed

- Refactored caching logic to use configuration-based cache prefixes and page paths

### Added

- `MultiThemePageTest` for rigorous verification of theme-isolated content

## [1.1.4] - 2026-03-20

### Added

- Automated cache prefixing for Page Builder based on the active theme

## [1.1.3] - 2026-03-19

### Added

- Support for HTML caching of compiled pages with configurable options

## [1.1.2] - 2026-03-18

### Changed

- Migrated theme settings configuration from `settings.json` to `config.json` for standardized naming
- Centralized theme path resolution within the `Theme` service

## [1.1.1] - 2026-03-17

### Fixed

- Initial theme settings configuration path resolution bug

## [1.1.0] - 2026-03-17

### Added

- Google Fonts integration with a new `GoogleFontField` and a comprehensive library of fonts
- `css_var` field on theme setting schema entries — the editor now syncs the declared CSS custom property to the preview iframe in real time as values change
- Per-setting reset icon in the Theme Settings panel (appears on hover when a value differs from its default)
- **Reset all** button in the Theme Settings panel header to restore every setting to its schema default in one action
- `resetThemeSetting(key)` and `resetAllThemeSettings()` methods on `PageManager`
- `updateCssVars(vars)` batch method on `PreviewManager` for single-message bulk CSS var updates
- `update-css-vars` message handler in `EditorPreviewRuntime` for batch CSS custom property application

### Changed

- `resetAllThemeSettings` uses a single `setThemeSettingsValues` store update and emits one `theme:settings-reset` event instead of N individual calls, avoiding N preview postMessages
- `updateThemeSetting` extracts a private `findSchemaSetting` helper shared with `resetThemeSetting`, eliminating duplicated schema traversal loops
- `ThemeSettingsPanel` now uses `ThemeSettingsGroup` and `SettingSchema` types instead of `any`, and imports `ResetIcon` from the shared icon registry

### Refactored

- Standardized theme setting keys in `ThemeSettingsPanel` for better consistency
- Improved code structure and readability in core editor components and services
- Enhanced page saving functionality within `PageManager`

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
