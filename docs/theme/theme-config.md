# Theme Configuration

The Page Builder is configured through a central configuration file and standard Laravel Service Providers.

## `config/pagebuilder.php`

Publish the config file if you haven't already:

```bash
php artisan vendor:publish --tag=pagebuilder-config
```

Key configuration keys:

- `sections`: The primary path for discovering section Blade files.
- `pages`: The path where JSON data and compiled pages are stored.

## The `pagebuilder` Disk

Ensure you have a `pagebuilder` disk defined in `config/filesystems.php` for asset storage:

```php
'disks' => [
    'pagebuilder' => [
        'driver' => 'local',
        'root' => storage_path('app/public/pagebuilder'),
        'url' => env('APP_URL').'/storage/pagebuilder',
        'visibility' => 'public',
    ],
],
```

## Adding Custom Paths

Use the `Section::add()` and `BlockCollection::add()` methods in your Service Provider to register theme-specific directories:

```php
use PageBuilder\Facades\Section;

public function boot()
{
    // Register theme sections
    Section::add(resource_path('views/theme/sections'));
}
```
