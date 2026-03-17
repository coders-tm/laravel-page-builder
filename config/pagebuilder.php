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

    'disk' => 'public',

    'asset_directory' => 'pagebuilder',

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
                    'key' => 'colors.text_secondary',
                    'label' => 'Text (secondary)',
                    'type' => 'color',
                    'default' => '#90c1cb',
                ],
                [
                    'key' => 'colors.primary',
                    'label' => 'Primary',
                    'type' => 'color',
                    'default' => '#10b981',
                ],
                [
                    'key' => 'colors.primary_hover',
                    'label' => 'Primary (hover)',
                    'type' => 'color',
                    'default' => '#059669',
                ],
                [
                    'key' => 'colors.accent',
                    'label' => 'Accent',
                    'type' => 'color',
                    'default' => '#10b981',
                ],
                [
                    'key' => 'colors.accent_hover',
                    'label' => 'Accent (hover)',
                    'type' => 'color',
                    'default' => '#059669',
                ],
                [
                    'key' => 'colors.background_light',
                    'label' => 'Background (light)',
                    'type' => 'color',
                    'default' => '#f8f6f6',
                ],
                [
                    'key' => 'colors.background_dark',
                    'label' => 'Background (dark)',
                    'type' => 'color',
                    'default' => '#0f0f0f',
                ],
                [
                    'key' => 'colors.bg_darker',
                    'label' => 'Background (darker)',
                    'type' => 'color',
                    'default' => '#0a0a0a',
                ],
                [
                    'key' => 'colors.bg_card',
                    'label' => 'Card background',
                    'type' => 'color',
                    'default' => '#1a1a1a',
                ],
                [
                    'key' => 'colors.bg_card_hover',
                    'label' => 'Card background (hover)',
                    'type' => 'color',
                    'default' => '#222222',
                ],
                [
                    'key' => 'colors.surface_dark',
                    'label' => 'Surface (dark)',
                    'type' => 'color',
                    'default' => '#271c1c',
                ],
                [
                    'key' => 'colors.border_dark',
                    'label' => 'Border (dark)',
                    'type' => 'color',
                    'default' => '#2a2a2a',
                ],
                [
                    'key' => 'colors.text_body',
                    'label' => 'Text (body)',
                    'type' => 'color',
                    'default' => '#a0a0a0',
                ],
                [
                    'key' => 'colors.text_muted',
                    'label' => 'Text (muted)',
                    'type' => 'color',
                    'default' => '#b99d9d',
                ],
                [
                    'key' => 'colors.neutral_surface',
                    'label' => 'Neutral surface',
                    'type' => 'color',
                    'default' => '#221010',
                ],
                [
                    'key' => 'colors.neutral_border',
                    'label' => 'Neutral border',
                    'type' => 'color',
                    'default' => '#392828',
                ],
                [
                    'key' => 'colors.neutral_text_dim',
                    'label' => 'Neutral text (dim)',
                    'type' => 'color',
                    'default' => '#b99d9d',
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
                    'default' => 'Inter, sans-serif',
                ],
                [
                    'key' => 'fonts.body',
                    'label' => 'Body font',
                    'type' => 'google_font',
                    'default' => 'Inter, sans-serif',
                ],
            ],
        ],

        [
            'name' => 'Radius & Shape',
            'settings' => [
                [
                    'key' => 'radius.base',
                    'label' => 'Radius (base)',
                    'type' => 'text',
                    'default' => '0.25rem',
                ],
                [
                    'key' => 'radius.lg',
                    'label' => 'Radius (lg)',
                    'type' => 'text',
                    'default' => '0.5rem',
                ],
                [
                    'key' => 'radius.xl',
                    'label' => 'Radius (xl)',
                    'type' => 'text',
                    'default' => '0.75rem',
                ],
                [
                    'key' => 'radius.full',
                    'label' => 'Radius (full)',
                    'type' => 'text',
                    'default' => '9999px',
                ],
            ],
        ],
    ],

    'theme_settings_path' => resource_path('theme-settings.json'),

    /*
    |--------------------------------------------------------------------------
    | Preserved Pages
    |--------------------------------------------------------------------------
    |
    | Define a list of slugs that are reserved by the system and cannot be
    | used when creating new dynamic pages in the page builder.
    |
    */

    'preserved_pages' => ['home'],

    /*
    |--------------------------------------------------------------------------
    | Page HTML Cache
    |--------------------------------------------------------------------------
    |
    | When enabled, the rendered HTML of each page is stored in the Laravel
    | cache and served on subsequent requests without re-rendering sections.
    | Cache is automatically invalidated when a page is saved or theme
    | settings change.
    |
    */

    'cache' => [
        'enabled' => env('PAGEBUILDER_CACHE_ENABLED', false),
        'ttl' => env('PAGEBUILDER_CACHE_TTL', 3600),
        'prefix' => 'pagebuilder.page',
    ],
];
