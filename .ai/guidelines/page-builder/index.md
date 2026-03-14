# Laravel Page Builder — AI Guidelines Index

This directory contains structured guidelines for AI agents (Cursor, Claude Code, GitHub Copilot, Codex, Gemini CLI) working with the `coderstm/laravel-page-builder` package.

---

## Files

| File | What It Covers |
|---|---|
| [core.md](core.md) | Package overview, five-layer architecture, DI rules, configuration, routes, code style |
| [layouts.md](layouts.md) | Page JSON structure, section/block fields, layout zones (header/footer), settings resolution |
| [sections.md](sections.md) | Section Blade files, `@schema()` directive, setting types, blocks array, SectionRegistry |
| [blocks.md](blocks.md) | Block Blade files, nesting, `@blocks()` directive, theme vs local blocks, BlockRegistry |
| [themes.md](themes.md) | Theme directory layout, master Blade layout, theme shadowing, assets, naming conventions |

---

## Quick Reference

### Create a section

1. Create `resources/views/sections/{type}.blade.php`
2. Add `@schema([...])` with `name`, `settings`, `blocks`, `presets`
3. Use `$section->settings->key` for settings
4. Use `@blocks($section)` to render blocks
5. Add `{!! $section->editorAttributes() !!}` to the root element

### Create a block

1. Create `resources/views/blocks/{type}.blade.php`
2. Add `@schema([...])` with `name`, `settings`, `blocks` (if container)
3. Use `$block->settings->key` for settings
4. Use `@blocks($block)` if it's a container block
5. Add `{!! $block->editorAttributes() !!}` to the root element

### Create a page JSON

```json
{
    "sections": {
        "unique-id": {
            "type": "section-type",
            "settings": {},
            "blocks": {},
            "order": []
        }
    },
    "order": ["unique-id"]
}
```

### Add a layout section

```json
{
    "layout": {
        "type": "page",
        "sections": {
            "header": {
                "type": "site-header",
                "settings": {},
                "blocks": {},
                "order": []
            }
        }
    }
}
```

---

## Architecture Summary

```
Blade @schema() → SchemaExtractor → SectionSchema / BlockSchema
                                           ↓
                              SectionRegistry / BlockRegistry
                                           ↓
                    Page JSON → Renderer::hydrateSection() → Section / Block
                                           ↓
                              Renderer::renderSection() → HTML
```

**Dependency rule:** `Schema → Registry → Components → Rendering → Services`

Lower layers never import from higher layers. All services are constructor-injected.

---

## Key Classes

| Class | Path | Purpose |
|---|---|---|
| `SectionSchema` | `src/Schema/SectionSchema.php` | Immutable section definition |
| `BlockSchema` | `src/Schema/BlockSchema.php` | Immutable block definition |
| `SettingSchema` | `src/Schema/SettingSchema.php` | Immutable setting definition |
| `SectionRegistry` | `src/Registry/SectionRegistry.php` | Discovers and provides section schemas |
| `BlockRegistry` | `src/Registry/BlockRegistry.php` | Discovers and provides block schemas |
| `SchemaExtractor` | `src/Registry/SchemaExtractor.php` | Parses `@schema()` from Blade files |
| `Renderer` | `src/Rendering/Renderer.php` | Core hydration and rendering engine |
| `Section` | `src/Components/Section.php` | Runtime section instance |
| `Block` | `src/Components/Block.php` | Runtime block instance |
| `Settings` | `src/Components/Settings.php` | Schema-aware settings bag |
| `PageData` | `src/Support/PageData.php` | Immutable page JSON value object |
| `PageStorage` | `src/Services/PageStorage.php` | Page JSON file I/O |
| `PageRenderer` | `src/Services/PageRenderer.php` | Full-page render orchestrator |

---

## Facades

```php
use Coderstm\PageBuilder\Facades\Section;   // → SectionRegistry
use Coderstm\PageBuilder\Facades\Block;     // → BlockRegistry
use Coderstm\PageBuilder\Facades\Page;      // → PageService
use Coderstm\PageBuilder\Facades\Theme;     // → Theme service
```

---

## Blade Directives

```blade
@schema([...])            {{-- no-op at render time; extracted at registration --}}
@blocks($section)         {{-- render all top-level blocks of a section --}}
@blocks($block)           {{-- render all child blocks of a container block --}}
@sections('header')       {{-- render a layout section slot --}}
@pbEditorClass            {{-- adds editor class to <html> tag when in editor mode --}}
@pbEditorScripts          {{-- injects editor interaction scripts --}}
```
