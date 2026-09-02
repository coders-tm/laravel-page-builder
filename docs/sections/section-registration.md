# Section Registration

The Page Builder automatically discovers sections in the default path, but you can also register custom paths for themes and modules.

## Default Path

The default path is `resources/views/sections/`. This is defined in `config/pagebuilder.php`:

```php
'sections' => resource_path('views/sections'),
```

## Custom Path Registration

You can add additional paths in your `AppServiceProvider` or a dedicated `ThemeServiceProvider` using the `Section` facade.

```php
use PageBuilder\Facades\Section;

public function boot()
{
    // Add a simple path
    Section::add(base_path('resources/views/sections'));

    // Add a path with a namespace
    Section::add([
        'path' => resource_path('views/theme/sections'),
        'namespace' => 'my-theme',
    ]);
}
```

## Programmatic Registration

If you need to register a section that doesn't exist as a Blade file (e.g., from a database or remote API), you can register a schema directly:

```php
use PageBuilder\Facades\Section;
use PageBuilder\Schema\SectionSchema;

Section::register('dynamic-section', new SectionSchema([
    'name' => 'Dynamic Section',
    'settings' => [ ... ],
]));
```

## Override Priority

When multiple paths are registered, the **last registered path wins** if there are duplicate section types. This allows themes to easily override core package sections by registering their paths later in the boot cycle.
