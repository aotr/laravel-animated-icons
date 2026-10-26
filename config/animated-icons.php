<?php

$autoInstall = env('ANIMATED_ICONS_AUTO_INSTALL');

return [

    /*
    |--------------------------------------------------------------------------
    | Icon map
    |--------------------------------------------------------------------------
    |
    | Starter set included with the package. Add registry icons with
    | `php artisan animateicons:sync <lucide-name>` or drop hand-authored
    | entries in here. Each icon is `markup` (inner SVG with data-part
    | markers) plus an `animation` map (part => keyframes/timing).
    |
    */

    'icons' => [
        'sprout' => [
            'markup' => '<g data-part="grow" style="transform-box: view-box; transform-origin: 12px 21px"><path d="M14 9.536V7a4 4 0 0 1 4-4h1.5a.5.5 0 0 1 .5.5V5a4 4 0 0 1-4 4 4 4 0 0 0-4 4c0 2 1 3 1 5a5 5 0 0 1-1 3"/><path data-part="leaf" style="transform-box: view-box; transform-origin: 12px 13px" d="M4 9a5 5 0 0 1 8 4 5 5 0 0 1-8-4"/></g><path d="M5 21h14"/>',
            'animation' => [
                'grow' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scale(1)'],
                        ['offset' => 0.3, 'transform' => 'scale(0.82)'],
                        ['offset' => 0.7, 'transform' => 'scale(1.06)'],
                        ['offset' => 1, 'transform' => 'scale(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
                'leaf' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'rotate(0deg)'],
                        ['offset' => 0.25, 'transform' => 'rotate(-12deg)'],
                        ['offset' => 0.5, 'transform' => 'rotate(8deg)'],
                        ['offset' => 0.75, 'transform' => 'rotate(-3deg)'],
                        ['offset' => 1, 'transform' => 'rotate(0deg)'],
                    ],
                    'duration' => 700,
                    'easing' => 'ease-in-out',
                    'delay' => 200,
                ],
            ],
        ],

        'shield-check' => [
            'markup' => '<path data-part="pulse" style="transform-box: view-box; transform-origin: 12px 12px" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path data-part="check" style="transform-box: view-box; transform-origin: 12px 12px" stroke-dasharray="9" stroke-dashoffset="0" d="m9 12 2 2 4-4"/>',
            'animation' => [
                'pulse' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scale(1)'],
                        ['offset' => 0.35, 'transform' => 'scale(1.06)'],
                        ['offset' => 0.7, 'transform' => 'scale(0.97)'],
                        ['offset' => 1, 'transform' => 'scale(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
                'check' => [
                    'keyframes' => [
                        ['offset' => 0, 'stroke-dashoffset' => 9, 'opacity' => 0, 'transform' => 'scale(0.8)'],
                        ['offset' => 0.25, 'stroke-dashoffset' => 9, 'opacity' => 0, 'transform' => 'scale(0.8)'],
                        ['offset' => 0.6, 'stroke-dashoffset' => 3, 'opacity' => 1, 'transform' => 'scale(1.12)'],
                        ['offset' => 1, 'stroke-dashoffset' => 0, 'opacity' => 1, 'transform' => 'scale(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-out',
                ],
            ],
        ],

        'radar' => [
            'markup' => '<path d="M19.07 4.93A10 10 0 0 0 6.99 3.34"/><path data-part="blip-a" style="transform-box: view-box; transform-origin: 4px 6px" d="M4 6h.01"/><path d="M2.29 9.62A10 10 0 1 0 21.31 8.35"/><path d="M16.24 7.76A6 6 0 1 0 8.23 16.67"/><path data-part="blip-b" style="transform-box: view-box; transform-origin: 12px 18px" d="M12 18h.01"/><path d="M17.99 11.66A6 6 0 0 1 15.77 16.67"/><circle cx="12" cy="12" r="2"/><path data-part="sweep" style="transform-box: view-box; transform-origin: 12px 12px" d="m13.41 10.59 5.66-5.66"/>',
            'animation' => [
                'sweep' => [
                    'keyframes' => [
                        ['transform' => 'rotate(0deg)'],
                        ['transform' => 'rotate(360deg)'],
                    ],
                    'duration' => 1000,
                    'easing' => 'ease-in-out',
                ],
                'blip-a' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scale(1)', 'opacity' => 1],
                        ['offset' => 0.2, 'transform' => 'scale(0)', 'opacity' => 0],
                        ['offset' => 0.6, 'transform' => 'scale(1.8)', 'opacity' => 1],
                        ['offset' => 1, 'transform' => 'scale(1)', 'opacity' => 1],
                    ],
                    'duration' => 400,
                    'easing' => 'ease-out',
                    'delay' => 550,
                ],
                'blip-b' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scale(1)', 'opacity' => 1],
                        ['offset' => 0.2, 'transform' => 'scale(0)', 'opacity' => 0],
                        ['offset' => 0.6, 'transform' => 'scale(1.8)', 'opacity' => 1],
                        ['offset' => 1, 'transform' => 'scale(1)', 'opacity' => 1],
                    ],
                    'duration' => 400,
                    'easing' => 'ease-out',
                    'delay' => 300,
                ],
            ],
        ],

        'chart-line' => [
            'markup' => '<path data-part="draw" pathLength="1" stroke-dasharray="1" d="M3 3v16a2 2 0 0 0 2 2h16"/><path data-part="draw" pathLength="1" stroke-dasharray="1" d="m19 9-5 5-4-4-3 3"/>',
            'animation' => [
                'root' => [
                    'keyframes' => [
                        ['transform' => 'scale(1)'],
                        ['offset' => 0.5, 'transform' => 'scale(1.05)'],
                        ['transform' => 'scale(1)'],
                    ],
                    'duration' => 400,
                    'easing' => 'ease-in-out',
                ],
                'draw' => [
                    'keyframes' => [
                        ['stroke-dashoffset' => 1, 'opacity' => 0.7],
                        ['stroke-dashoffset' => 0, 'opacity' => 1],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'user-check' => [
            'markup' => '<path data-part="tick" style="transform-box: view-box; transform-origin: 19px 11px" stroke-dasharray="9" stroke-dashoffset="0" d="m16 11 2 2 4-4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle data-part="head" cx="9" cy="7" r="4"/>',
            'animation' => [
                'tick' => [
                    'keyframes' => [
                        ['offset' => 0, 'stroke-dashoffset' => 9, 'opacity' => 0, 'transform' => 'scale(0.8)'],
                        ['offset' => 0.25, 'stroke-dashoffset' => 9, 'opacity' => 0, 'transform' => 'scale(0.8)'],
                        ['offset' => 0.6, 'stroke-dashoffset' => 3, 'opacity' => 1, 'transform' => 'scale(1.12)'],
                        ['offset' => 1, 'stroke-dashoffset' => 0, 'opacity' => 1, 'transform' => 'scale(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-out',
                ],
                'head' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateY(0)'],
                        ['offset' => 0.35, 'transform' => 'translateY(-1.5px)'],
                        ['offset' => 0.7, 'transform' => 'translateY(0.5px)'],
                        ['offset' => 1, 'transform' => 'translateY(0)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'handshake' => [
            'markup' => '<g data-part="shake" style="transform-box: view-box; transform-origin: 12px 12px"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></g>',
            'animation' => [
                'shake' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateY(0) rotate(0deg)'],
                        ['offset' => 0.25, 'transform' => 'translateY(1.2px) rotate(-4deg)'],
                        ['offset' => 0.5, 'transform' => 'translateY(-1.2px) rotate(4deg)'],
                        ['offset' => 0.75, 'transform' => 'translateY(0.8px) rotate(-3deg)'],
                        ['offset' => 1, 'transform' => 'translateY(0) rotate(0deg)'],
                    ],
                    'duration' => 800,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'chevron-down' => [
            'markup' => '<path data-part="nudge" d="m6 9 6 6 6-6"/>',
            'animation' => [
                'nudge' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateY(0)'],
                        ['offset' => 0.4, 'transform' => 'translateY(2.5px)'],
                        ['offset' => 0.75, 'transform' => 'translateY(-0.625px)'],
                        ['offset' => 1, 'transform' => 'translateY(0)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'circle-question-mark' => [
            'markup' => '<circle data-part="shape" style="transform-box: view-box; transform-origin: 12px 12px" cx="12" cy="12" r="10"/><path data-part="curl" style="transform-box: view-box; transform-origin: 12px 13px" d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path data-part="dot" d="M12 17h.01"/>',
            'animation' => [
                'shape' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scale(1)'],
                        ['offset' => 0.35, 'transform' => 'scale(1.1)'],
                        ['offset' => 0.7, 'transform' => 'scale(0.96)'],
                        ['offset' => 1, 'transform' => 'scale(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
                'curl' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'rotate(0deg)'],
                        ['offset' => 0.2, 'transform' => 'rotate(-12deg)'],
                        ['offset' => 0.5, 'transform' => 'rotate(10deg)'],
                        ['offset' => 0.8, 'transform' => 'rotate(-4deg)'],
                        ['offset' => 1, 'transform' => 'rotate(0deg)'],
                    ],
                    'duration' => 700,
                    'easing' => 'ease-in-out',
                ],
                'dot' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateY(0)'],
                        ['offset' => 0.35, 'transform' => 'translateY(-1.5px)'],
                        ['offset' => 0.7, 'transform' => 'translateY(0.45px)'],
                        ['offset' => 1, 'transform' => 'translateY(0)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                    'delay' => 100,
                ],
            ],
        ],

        'arrow-right' => [
            'markup' => '<g data-part="arrow"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></g>',
            'animation' => [
                'arrow' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateX(0)'],
                        ['offset' => 0.3, 'transform' => 'translateX(-1.2px)'],
                        ['offset' => 0.7, 'transform' => 'translateX(1.6px)'],
                        ['offset' => 1, 'transform' => 'translateX(0)'],
                    ],
                    'duration' => 500,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'user-round' => [
            'markup' => '<circle data-part="head" cx="12" cy="8" r="5"/><path data-part="body" style="transform-box: view-box; transform-origin: 12px 21px" d="M20 21a8 8 0 0 0-16 0"/>',
            'animation' => [
                'head' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'translateY(0)'],
                        ['offset' => 0.35, 'transform' => 'translateY(-1.5px)'],
                        ['offset' => 0.7, 'transform' => 'translateY(0.5px)'],
                        ['offset' => 1, 'transform' => 'translateY(0)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
                'body' => [
                    'keyframes' => [
                        ['offset' => 0, 'transform' => 'scaleY(1)'],
                        ['offset' => 0.35, 'transform' => 'scaleY(0.88)'],
                        ['offset' => 0.7, 'transform' => 'scaleY(1.04)'],
                        ['offset' => 1, 'transform' => 'scaleY(1)'],
                    ],
                    'duration' => 600,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Generated file
    |--------------------------------------------------------------------------
    |
    | Written by `animateicons:sync` and local auto-install. Keep it out of
    | hand edits; hand-authored icons belong in the `icons` map above.
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