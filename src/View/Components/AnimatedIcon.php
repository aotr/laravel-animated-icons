<?php

namespace Aotr\AnimatedIcons\View\Components;

use Aotr\AnimatedIcons\Actions\SyncAnimatedIconAction;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Renders one animated icon. Unknown icons auto-install in local (configurable
 * with `animated-icons.auto_install`); testing fails loudly and production
 * renders nothing. The `class` attribute applies to the SVG, so size and color
 * utilities work as expected.
 */
class AnimatedIcon extends Component
{
    public function __construct(
        public string $name,
        public string $trigger = 'hover',
        public ?string $speed = null,
    ) {
        if (! in_array($trigger, ['hover', 'click', 'auto', 'visible', 'none'], true)) {
            throw new InvalidArgumentException("Invalid animated icon trigger [{$trigger}].");
        }

        if ($speed !== null && ! in_array($speed, ['fast', 'normal', 'slow'], true)) {
            throw new InvalidArgumentException("Invalid animated icon speed [{$speed}].");
        }
    }

    public function render(): View
    {
        $icon = config("animated-icons.icons.{$this->name}");

        if (! is_array($icon)) {
            $icon = $this->installOrFail();
        }

        return view('animated-icons::components.icon', [
            'name' => $this->name,
            'icon' => $icon,
            'trigger' => $this->trigger,
            'speed' => $this->speed,
        ]);
    }

    /**
     * @return array{markup: string, animation: array<string, mixed>}|null
     */
    private function installOrFail(): ?array
    {
        if (! $this->shouldInstall()) {
            if (app()->environment('testing')) {
                throw new InvalidArgumentException("Animated icon [{$this->name}] is not defined.");
            }

            return null;
        }

        if (app()->configurationIsCached()) {
            throw new RuntimeException('Configuration is cached — run php artisan config:clear before using new icons.');
        }

        try {
            $result = app(SyncAnimatedIconAction::class)->handle($this->name);

            config(["animated-icons.icons.{$this->name}" => $result['entry']]);

            return $result['entry'];
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Animated icon [{$this->name}] could not be installed: {$e->getMessage()}", 0, $e);
        }
    }

    private function shouldInstall(): bool
    {
        $setting = config('animated-icons.auto_install');

        if ($setting === true) {
            return true;
        }

        if ($setting === false) {
            return false;
        }

        return app()->environment('local');
    }
}
