---
title: Multilanguage
---

# Multilanguage

Laravel Page Builder supports multilanguage page resolution. When a language is set, the system first looks for locale-specific files before falling back to the default.

## Configuration

Define available languages in `config/pagebuilder.php`:

```php
'languages' => [
    ['code' => 'en', 'name' => 'English'],   // default language
    ['code' => 'fr', 'name' => 'Français'],
    ['code' => 'es', 'name' => 'Español'],
],
```

When `languages` is empty, multilanguage is disabled and no language selector appears in the editor.

## File Resolution

When a language is set (e.g. `fr`), files are resolved in this order:

| Layer         | Default                  | Locale-specific                        |
| ------------- | ------------------------ | -------------------------------------- |
| Page JSON     | `pages/{slug}.json`      | `pages/{slug}.fr.json` → fallback      |
| Custom Blade  | `pages/{slug}.blade.php` | `pages/{slug}.fr.blade.php` → fallback |
| Template JSON | `templates/{name}.json`  | `templates/{name}.fr.json` → fallback  |

If the locale-specific file exists, it is used. Otherwise, the system falls back to the default file.

## Setting the Language

### From PHP

```php
use PageBuilder\PageBuilder;

// Set language for the current request
PageBuilder::setLang('fr');

// Get the current language (null = default)
$lang = PageBuilder::getLang();

// Reset to default language
PageBuilder::setLang(null);
```

### From Middleware

The `lang` middleware alias is registered automatically:

```php
// Route-level language
Route::get('/fr/{slug}', [WebPageController::class, 'pages'])
    ->middleware('lang:fr');

// Group middleware
Route::middleware(['lang:fr'])->group(function () {
    // All pages resolve French locale files
});
```

The `SetLangMiddleware` reads the language from a route parameter or `?lang=` query parameter.

### From Service Provider or Controller

```php
use PageBuilder\PageBuilder;

// In a service provider's boot() method
PageBuilder::setLang(app()->getLocale());

// In a controller
public function show(Request $request)
{
    PageBuilder::setLang($request->input('lang'));
    return Page::render('about');
}
```

## Editor Integration

When languages are configured, the editor header shows a **Globe icon**. Clicking it opens a popover with available languages.

- Each language shows its name and code (e.g., "Français fr")
- The default language shows a "(Default)" badge
- Selecting a language automatically reloads the current page in that language
- The language is persisted in the URL (`?lang=fr`) across page switches

## How It Works

### Backend Flow

1. Language is set via `PageBuilder::setLang('fr')`
2. `PageStorage::load('home')` resolves `pages/home.fr.json` first
3. If not found, falls back to `pages/home.json`
4. `PageStorage::save('home', $data)` saves to `pages/home.fr.json`

### Frontend Flow

1. User selects language in the editor header
2. `NavigationManager.setLang('fr')` updates URL to `?lang=fr`
3. `loadPage()` calls `api.getPage('home', 'fr')` → `GET /pagebuilder/home.json?lang=fr`
4. Backend controller sets language and resolves locale-specific file
5. `savePage()` calls `api.savePage('home', $data, $meta, $themeSettings, 'fr')` → `POST /pagebuilder/home` with `lang: "fr"`
6. Backend controller sets language and saves to locale-specific path

## File Structure Example

```
resources/views/pages/
├── home.json              # default language
├── home.fr.json           # French
├── home.es.json           # Spanish
├── home.blade.php         # default custom view
├── home.fr.blade.php      # French custom view
├── about.json
├── about.fr.json
└── about.es.json
```

## Best Practices

- Always define the default language as the first entry in the `languages` array
- Use standard ISO 639-1 language codes (e.g., `en`, `fr`, `es`, `de`)
- Locale-specific files are optional — fall back to default when content is the same
- The editor saves to the correct locale path automatically based on the active language
