<?php

namespace Aotr\AnimatedIcons\View\Components;

use Aotr\AnimatedIcons\AnimatedIcons;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Emits the page manifest (inert JSON, CSP-friendly) and the runtime script.
 * Include it once, after the page content so auto-installed icons are in the
 * manifest.
 */
class AnimatedIconsAssets extends Component
{
    public function render(): View
    {
        /** @var Collection<string, array<string, mixed>> $manifest */
        $manifest = collect(config('animated-icons.icons', []))
            ->filter(fn (mixed $icon): bool => is_array($icon) && isset($icon['animation']))
            ->map(fn (array $icon): array => $icon['animation']);

        return view('animated-icons::components.assets', [
            'manifest' => $manifest,
            'asset' => route('animated-icons.asset', ['v' => AnimatedIcons::assetVersion()]),
            'style' => route('animated-icons.style', ['v' => AnimatedIcons::styleVersion()]),
        ]);
    }
}
