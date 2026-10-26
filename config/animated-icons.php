<?php

$autoInstall = env('ANIMATED_ICONS_AUTO_INSTALL');

return [

    /*
    |--------------------------------------------------------------------------
    | Icon map
    |--------------------------------------------------------------------------
    |
    | Each icon is a `markup` string (the inner SVG, with data-part markers)
    | and an `animation` map (part => keyframes/timing). Add registry icons
    | with `php artisan animateicons:sync <lucide-name>`; hand-authored icons
    | can be added here directly.
    |
    */

    'icons' => [],

    /*
    |--------------------------------------------------------------------------
    | Generated file
    |--------------------------------------------------------------------------
    |
    | The file written by `animateicons:sync` and by local auto-install. Keep
    | it out of hand edits; hand-authored icons belong in the host app's icon
    | config.
    |
    */

    'generated_path' => env('ANIMATED_ICONS_PATH', config_path('animated-icons-generated.php')),

    /*
    |--------------------------------------------------------------------------
    | Auto-install
    |--------------------------------------------------------------------------
    |
    | null: install missing icons on first use only in the local environment.
    | true / false: force on or off.
    |
    */

    'auto_install' => $autoInstall === null ? null : filter_var($autoInstall, FILTER_VALIDATE_BOOLEAN),

];
