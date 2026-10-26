# Laravel Animated Icons

Plug-and-play animated SVG icons for Laravel Blade. One component, one page
manifest, no JavaScript build step.

- **Zero JS build** — the runtime is served by the package.
- **Triggers** — hover (default), click, auto, on-view, or manual.
- **Any DOM update works** — Livewire/Alpine-inserted icons animate without re-init.
- **Registry sync** — `php artisan animateicons:sync bell-ring` imports an icon.
- **Accessible** — `aria-hidden`, `prefers-reduced-motion` respected.
- **CSP-friendly** — keyframes live in an inert JSON block, not inline JS.

## Installation

```bash
composer require aotr/laravel-animated-icons
```

Until the package is published, install it from a path or VCS repository:

```json
"repositories": [
    { "type": "path", "url": "../laravel-animated-icons", "options": { "symlink": true } }
]
```

```bash
composer require aotr/laravel-animated-icons:^1.0
```

Publish the config if you want to edit it:

```bash
php artisan vendor:publish --tag=animated-icons-config
```

Add the runtime once, after your content (every layout that can render icons):

```blade
<x-animated-icons-assets />
```

## Usage

```blade
<x-animated-icon name="sprout" class="h-5 w-5" />
```

`class` applies to the SVG, so Tailwind size/color utilities work.

### Triggers

```blade
<x-animated-icon name="bell-ring" />                          {{-- hover --}}
<x-animated-icon name="bell-ring" trigger="click" />           {{-- click --}}
<x-animated-icon name="shield-check" trigger="auto" />         {{-- once it becomes visible --}}
<x-animated-icon name="chart-line" trigger="visible" />        {{-- on scroll into view --}}
<x-animated-icon name="radar" trigger="none" />                {{-- manual only --}}
```

Card/button hover is automatic: any `button`, `a`, or element with
`data-animated-icon-trigger` that contains icons drives them.

### Speed

```blade
<x-animated-icon name="chevron-down" speed="fast" />
<x-animated-icon name="hammer" speed="slow" />
```

### Manual / event-driven

```js
window.AnimateIcons.play(document.querySelector('[data-animated-icon="shield-check"]'));
window.AnimateIcons.playAll(); // every icon on the page

document.dispatchEvent(new CustomEvent('animated-icon:play', {
    detail: { name: 'radar' },
}));

// or a selector
document.dispatchEvent(new CustomEvent('animated-icon:play', {
    detail: { selector: '.js-broker-status' },
}));
```

Useful after Livewire dispatches, a broker callback, a Pusher event, or a
successful form submit.

## Adding icons

```bash
php artisan animateicons:sync heart
php artisan animateicons:sync bell-ring wifi map-pin --dry-run
```

The command fetches the icon from the [AnimateIcons](https://animateicons.in)
registry, converts its Motion variants to Web Animations keyframes, and writes
it to `animated-icons.generated_path` (default
`config/animated-icons-generated.php`).

In local, the first use of an unknown icon installs it automatically. In
production it never fetches while rendering; a missing icon renders nothing.
Set `animated-icons.auto_install` to `true`/`false` to override.

### Hand-authored icons

Add entries to `animated-icons.icons` in the published config:

```php
'icons' => [
    'my-glyph' => [
        'markup' => '<g data-part="root"><path d="..."/></g>',
        'animation' => [
            'root' => [
                'keyframes' => [
                    ['transform' => 'scale(1)'],
                    ['offset' => 0.5, 'transform' => 'scale(1.08)'],
                    ['transform' => 'scale(1)'],
                ],
                'duration' => 450,
                'easing' => 'ease-in-out',
            ],
        ],
    ],
],
```

A part maps to `data-part="..."` in the markup. A part may have one track or a
list of tracks (for per-property timings).

## Livewire

The runtime is delegated on `document`, so icons inside Livewire components —
including conditional icons inserted after a validation error or state change —
work without any `wire:` wiring.

## Testing

```bash
composer test
```

`AnimateIcons` is ported from the [AnimateIcons](https://animateicons.in)
registry. Lucide icons are ISC-licensed; check the registry's terms before
redistributing its animation data.

## License

MIT.
