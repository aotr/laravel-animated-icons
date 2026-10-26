<?php

namespace Aotr\AnimatedIcons\Http\Controllers;

use Illuminate\Http\Response;

class AnimatedIconsAssetController
{
    public function __invoke(): Response
    {
        return response(file_get_contents(__DIR__.'/../../../resources/js/animated-icons.js'), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function style(): Response
    {
        return response(file_get_contents(__DIR__.'/../../../resources/css/animated-icons.css'), 200, [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
