<?php

namespace Aotr\AnimatedIcons;

final class AnimatedIcons
{
    public const VERSION = '1.0.0';

    /**
     * The runtime is served straight from the package, so use the file's
     * timestamp as the cache-busting version.
     */
    public static function assetVersion(): string
    {
        $path = __DIR__.'/../resources/js/animated-icons.js';

        return is_file($path) ? (string) filemtime($path) : self::VERSION;
    }
}
