# Changelog

All notable changes to `laravel-animated-icons` are documented here.

## 1.0.2 — 2026-10-26

- Serve the runtime JS/CSS as normal responses, fixing empty file responses on
  some HTTP servers and test runners.

## 1.0.1 — 2026-10-26

- Ship the runtime stylesheet with the package (`/vendor/animated-icons.css`)
  so fresh installs need no host CSS for transform origins.
- Add the stylesheet link to the assets component.

## 1.0.0 — 2026-10-26

- Initial release.
- `<x-animated-icon>` with hover, click, auto, on-view and manual triggers.
- `<x-animated-icons-assets>` with an inert JSON manifest and a cacheable runtime route.
- Delegated triggers for Livewire/Alpine-inserted icons.
- `animateicons:sync` registry importer with atomic generated-file writes.
- Local auto-install, per-icon speed, reduced motion and multi-track keyframes.
- Ten starter icons included.
