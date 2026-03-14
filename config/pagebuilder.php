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

    'theme_settings_schema' => [],

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
];
