<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Editor Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options for the content editor system,
    | including page registry management and file paths.
    |
    */

    // Base URL path for the page builder routes (e.g., 'pagebuilder' or 'foo')
    // The editor will be accessible at /{prefix}/* and API endpoints at /{prefix}/*
    'prefix' => env('PAGEBUILDER_PREFIX', 'pagebuilder'),

    // The prefix for the page builder public pages and editor.
    // If your pages are served at /foo/*, set this to 'foo'.
    'basePath' => env('PAGEBUILDER_BASE_PATH', '/'),

    // Additional query parameters to preserve during editor navigation
    'preserved_params' => [],

    // Path to the pages directory (JSON data files)
    'pages' => resource_path('views/pages'),

    // Path to the sections directory (Blade section templates)
    'sections' => resource_path('views/sections'),

    // Path to the theme blocks directory (Blade block templates)
    'blocks' => resource_path('views/blocks'),

    // Path to the templates directory (JSON template files)
    'templates' => resource_path('views/templates'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware groups applied to page builder routes. You should add
    | authentication middleware here to protect the editor and API.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Asset Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for the asset upload and storage system used by the
    | page builder's image picker and media management.
    |
    */

    'disk' => env('PAGEBUILDER_DISK', 'public'),

    'asset_directory' => env('PAGEBUILDER_ASSET_DIRECTORY', 'pagebuilder'),

    /*
    |--------------------------------------------------------------------------
    | Theme Settings
    |--------------------------------------------------------------------------
    |
    | Define global theme settings that can be edited from the page builder.
    | The schema defines available settings and their types, while values
    | are persisted to a JSON file.
    |
    | 'theme_settings_schema' — array of setting group definitions
    | 'theme_settings_path'   — path to the JSON file storing current values
    |
    */

    'theme_settings_schema' => [
        [
            'name' => 'Colors',
            'settings' => [
                [
                    'key' => 'colors.primary',
                    'label' => 'Primary',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(168 94% 7%)',
                    'css_var' => '--color-primary',
                ],
                [
                    'key' => 'colors.primary_hover',
                    'label' => 'Primary (hover)',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(165 86% 16%)',
                    'css_var' => '--color-primary-hover',
                ],
                [
                    'key' => 'colors.secondary',
                    'label' => 'Secondary',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(50 100% 50%)',
                    'css_var' => '--color-secondary',
                ],
                [
                    'key' => 'colors.tertiary',
                    'label' => 'Tertiary',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(199 89% 48%)',
                    'css_var' => '--color-tertiary',
                ],
                [
                    'key' => 'colors.background',
                    'label' => 'Background',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(210 31% 98%)',
                    'css_var' => '--color-background',
                ],
                [
                    'key' => 'colors.surface',
                    'label' => 'Surface',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(0 0% 100%)',
                    'css_var' => '--color-surface',
                ],
                [
                    'key' => 'colors.card',
                    'label' => 'Card',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(0 0% 100%)',
                    'css_var' => '--color-card',
                ],
                [
                    'key' => 'colors.warm',
                    'label' => 'Warm background',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(210 40% 96%)',
                    'css_var' => '--color-warm',
                ],
                [
                    'key' => 'colors.border',
                    'label' => 'Border',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(214 32% 91%)',
                    'css_var' => '--color-border',
                ],
                [
                    'key' => 'colors.body',
                    'label' => 'Text (body)',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(222 47% 11%)',
                    'css_var' => '--color-body',
                ],
                [
                    'key' => 'colors.muted',
                    'label' => 'Text (muted)',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(215 19% 35%)',
                    'css_var' => '--color-muted',
                ],
                [
                    'key' => 'colors.on_primary',
                    'label' => 'Text on primary',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(0 0% 100%)',
                    'css_var' => '--color-on-primary',
                ],
                [
                    'key' => 'colors.on_surface',
                    'label' => 'Text on surface',
                    'type' => 'color',
                    'mode' => 'hsl',
                    'default' => 'hsl(222 47% 11%)',
                    'css_var' => '--color-on-surface',
                ],
            ],
        ],

        [
            'name' => 'Typography',
            'settings' => [
                [
                    'key' => 'fonts.display',
                    'label' => 'Display font',
                    'type' => 'google_font',
                    'default' => 'Instrument Sans, Inter, sans-serif',
                    'css_var' => '--font-display',
                ],
                [
                    'key' => 'fonts.body',
                    'label' => 'Body font',
                    'type' => 'google_font',
                    'default' => 'Instrument Sans, Inter, sans-serif',
                    'css_var' => '--font-body',
                ],
            ],
        ],

        [
            'name' => 'Radius & Shape',
            'settings' => [
                [
                    'key' => 'radius.sm',
                    'label' => 'Radius (sm)',
                    'type' => 'text',
                    'default' => '0.375rem',
                    'css_var' => '--radius-sm',
                ],
                [
                    'key' => 'radius.md',
                    'label' => 'Radius (md)',
                    'type' => 'text',
                    'default' => '0.5rem',
                    'css_var' => '--radius-md',
                ],
                [
                    'key' => 'radius.lg',
                    'label' => 'Radius (lg)',
                    'type' => 'text',
                    'default' => '0.75rem',
                    'css_var' => '--radius-lg',
                ],
                [
                    'key' => 'radius.xl',
                    'label' => 'Radius (xl)',
                    'type' => 'text',
                    'default' => '1.5rem',
                    'css_var' => '--radius-xl',
                ],
                [
                    'key' => 'radius.2xl',
                    'label' => 'Radius (2xl)',
                    'type' => 'text',
                    'default' => '2rem',
                    'css_var' => '--radius-2xl',
                ],
                [
                    'key' => 'radius.full',
                    'label' => 'Radius (full)',
                    'type' => 'text',
                    'default' => '9999px',
                    'css_var' => '--radius-full',
                ],
            ],
        ],
    ],

    'theme_settings_path' => resource_path('settings.json'),

    /*
    |--------------------------------------------------------------------------
    | Preserved Pages
    |--------------------------------------------------------------------------
    |
    | Define a list of slugs that are reserved by the system and cannot be
    | used when creating new dynamic pages in the page builder.
    |
    */

    'preserved_pages' => [
        'home',
        'admin',
        'user',
        'api',
        'storage',
        'uploads',
        'files',
        'vendor',
    ],

];
