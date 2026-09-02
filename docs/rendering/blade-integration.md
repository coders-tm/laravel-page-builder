# Blade Integration

The Page Builder provides several custom Blade directives to make theme development seamless.

## Core Directives

### `@schema`

Used inside section and block files to define their configuration. This directive is parsed statically and does not output anything at runtime.

### `@blocks($parent)`

Iterates through and renders all child blocks for a given parent (a `$section` or another `$block`).

```blade
<div class="column">
    @blocks($block)
</div>
```

### `@sections($key, $overrides)`

Used in layout files to render specific layout zones (e.g., header, footer).

```blade
@sections('header')
```

### `@pbEditorClass`

Renders the full `<html>` class attribute. Any classes you pass are included first, then editor classes are added when the page is viewed inside the editor.

```blade
<html @pbEditorClass('dark', 'theme-default')>
```

## Component Variables

Every section and block template has access to a specialized instance variable:

- `$section`: Instance of `PageBuilder\Components\Section`.
- `$block`: Instance of `PageBuilder\Components\Block`.

These objects provide methods like `editorAttributes()` and properties like `settings` and `blocks` (for containers).

## Manual Rendering

You can also render sections manually from your own controllers or views using the `Renderer` service:

```php
use PageBuilder\Rendering\Renderer;

$html = app(Renderer::class)->renderRawSection('hero', [
    'settings' => ['title' => 'Custom Title'],
]);
```
