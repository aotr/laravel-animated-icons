<?php

namespace Aotr\AnimatedIcons\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnimatedIconsAssetController
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(__DIR__.'/../../../resources/js/animated-icons.js', [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
