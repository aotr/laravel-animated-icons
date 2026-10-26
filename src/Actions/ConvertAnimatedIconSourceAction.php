<?php

namespace Aotr\AnimatedIcons\Actions;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Converts an AnimateIcons registry source (React + motion TSX) into our
 * plain-SVG config entry: `markup` with `data-part` markers and per-part Web
 * Animations keyframes.
 *
 * Supported registry constructs: object variants, variant factories with
 * literal arguments (or a literal `custom`), transform / opacity / pathLength /
 * strokeDashoffset properties, per-property timings, and the SVG elements the
 * Lucide set uses. Anything else fails loudly instead of writing a half-working
 * icon.
 */
class ConvertAnimatedIconSourceAction
{
    /** @var list<string> */
    private const TRANSFORM_PROPERTIES = ['x', 'y', 'translateX', 'translateY', 'rotate', 'scale', 'scaleX', 'scaleY'];

    /** @var list<string> */
    private const TIMING_KEYS = ['duration', 'delay', 'ease', 'times', 'repeat', 'repeatType'];

    /** @var list<string> */
    private const ANIMATED_PROPERTIES = ['opacity', 'pathLength', 'strokeDashoffset'];

    /** @var list<string> */
    private const ALLOWED_TAGS = ['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'g', 'defs', 'clipPath', 'mask', 'title'];

    /** @var list<string> */
    private const SKIPPED_ATTRIBUTES = ['variants', 'custom', 'style', 'className', 'initial', 'animate', 'transition', 'whileHover', 'whileTap'];

    /**
     * @return array{markup: string, animation: array<string, mixed>}
     */
    public function handle(string $source): array
    {
        [$svgAttributes, $inner] = $this->extractSvg($source);

        $variants = $this->parseVariants($source);

        if ($variants === []) {
            throw new RuntimeException('No animated variants found in the registry source.');
        }

        $rootUsage = $this->usageFromAttributes($this->parseAttributes($svgAttributes), $variants);

        $usages = $this->collectUsages($inner, $variants);

        $usages = $this->assignParts($usages);

        $markup = $this->renderMarkup($inner, $usages);

        $animation = [];

        if ($rootUsage !== null) {
            $animation['root'] = $this->variantAnimation($rootUsage['variant'], $rootUsage['params'], $variants);
        }

        foreach ($usages as $usage) {
            if ($usage['part'] === null || isset($animation[$usage['part']])) {
                continue;
            }

            $animation[$usage['part']] = $this->variantAnimation($usage['variant'], $usage['params'], $variants);
        }

        if ($animation === []) {
            throw new RuntimeException('The icon has no animated variants.');
        }

        return ['markup' => $markup, 'animation' => $animation];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractSvg(string $source): array
    {
        if (! preg_match('/<m\.svg\b([^>]*)>(.*?)<\/m\.svg>/s', $source, $matches)) {
            throw new RuntimeException('Could not find the SVG in the registry source.');
        }

        return [$matches[1], trim($matches[2])];
    }

    /**
     * @return array<string, array{params: list<string>, body: string}>
     */
    private function parseVariants(string $source): array
    {
        $variants = [];

        preg_match_all('/const\s+([A-Za-z_]\w*Variants)\b/', $source, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as [$name, $offset]) {
            $after = substr($source, $offset + strlen($name));
            $equals = strpos($after, '=');

            if ($equals === false) {
                continue;
            }

            $rest = ltrim(substr($after, $equals + 1));

            if (str_starts_with($rest, '{')) {
                [$body] = $this->readBalanced($rest, 0);
                $variants[$name] = ['params' => $this->animateFunctionParams($body), 'body' => $body];

                continue;
            }

            if (str_starts_with($rest, '(')) {
                [$paramsRaw, $position] = $this->readBalanced($rest, 0, '(', ')');
                $rest = ltrim(substr($rest, $position));

                $arrow = strpos($rest, '=>');

                if ($arrow === false) {
                    continue;
                }

                $rest = ltrim(substr($rest, $arrow + 2));

                if (str_starts_with($rest, '(')) {
                    [$parenthesised, $position] = $this->readBalanced($rest, 0, '(', ')');
                    $rest = ltrim($parenthesised);
                }

                if (! str_starts_with($rest, '{')) {
                    continue;
                }

                [$body] = $this->readBalanced($rest, 0);

                $variants[$name] = [
                    'params' => array_merge($this->paramNames($paramsRaw), $this->animateFunctionParams($body)),
                    'body' => $body,
                ];
            }
        }

        return $variants;
    }

    /**
     * A variant's `animate` state may be a function of its own (`animate: (at) => ({...})`).
     * Those parameters are supplied per element through `custom`.
     *
     * @return list<string>
     */
    private function animateFunctionParams(string $body): array
    {
        $animate = $this->objectProperty($body, 'animate');

        if ($animate === null) {
            return [];
        }

        $arrow = $this->arrowFunction($animate);

        return $arrow === null ? [] : $arrow['params'];
    }

    /**
     * @return list<string>
     */
    private function paramNames(string $params): array
    {
        $names = [];

        foreach ($this->splitTopLevel($params) as $param) {
            $param = trim(explode(':', explode('=', $param)[0])[0]);

            if ($param !== '') {
                $names[] = $param;
            }
        }

        return $names;
    }

    /**
     * @return array{params: list<string>, body: string}|null
     */
    private function arrowFunction(string $raw): ?array
    {
        $raw = trim($raw);

        if (! str_starts_with($raw, '(')) {
            return null;
        }

        [$params, $position] = $this->readBalanced($raw, 0, '(', ')');
        $rest = ltrim(substr($raw, $position));

        if (! str_starts_with($rest, '=>')) {
            return null;
        }

        $rest = ltrim(substr($rest, 2));

        if (str_starts_with($rest, '(')) {
            [$rest, $position] = $this->readBalanced($rest, 0, '(', ')');
            $rest = ltrim($rest);
        }

        if (! str_starts_with($rest, '{')) {
            throw new RuntimeException('Unsupported arrow-function animation body.');
        }

        [$body] = $this->readBalanced($rest, 0);

        return ['params' => $this->paramNames($params), 'body' => $body];
    }

    /**
     * @param  array<string, array{value: string, quoted: bool}>  $attributes
     * @param  array<string, array{params: list<string>, body: string}>  $variants
     * @return array{variant: string, params: array<string, float>}|null
     */
    private function usageFromAttributes(array $attributes, array $variants): ?array
    {
        if (! isset($attributes['variants'])) {
            return null;
        }

        return $this->resolveVariantExpression($attributes['variants']['value'], $attributes, $variants);
    }

    /**
     * @param  array<string, array{value: string, quoted: bool}>  $attributes
     * @param  array<string, array{params: list<string>, body: string}>  $variants
     * @return array{variant: string, params: array<string, float>}
     */
    private function resolveVariantExpression(string $expression, array $attributes, array $variants): array
    {
        $expression = trim($expression);

        if (! preg_match('/^([A-Za-z_]\w*)(?:\((.*)\))?$/s', $expression, $matches)) {
            throw new RuntimeException("Unsupported variant expression [{$expression}].");
        }

        $name = $matches[1];

        if (! isset($variants[$name])) {
            throw new RuntimeException("Unknown variant [{$name}].");
        }

        $args = [];

        if (isset($matches[2]) && trim($matches[2]) !== '') {
            foreach ($this->splitTopLevel($matches[2]) as $argument) {
                $args[] = $this->evaluate($argument);
            }
        }

        $params = $variants[$name]['params'];

        if ($args === [] && $params !== []) {
            if (isset($attributes['custom']) && count($params) === 1) {
                $args[] = $this->evaluate($attributes['custom']['value']);
            } else {
                throw new RuntimeException("Variant [{$name}] requires arguments.");
            }
        }

        if (count($args) !== count($params)) {
            throw new RuntimeException("Variant [{$name}] expects ".count($params).' arguments.');
        }

        /** @var array<string, float> $values */
        $values = $params === [] ? [] : array_combine($params, $args);

        return ['variant' => $name, 'params' => $values];
    }

    /**
     * @param  array<string, array{params: list<string>, body: string}>  $variants
     * @return list<array{tag: string, attributes: array<string, array{value: string, quoted: bool}>, selfClosing: bool, variant: string, params: array<string, float>, part: string|null, usesPathLength: bool}>
     */
    private function collectUsages(string $inner, array $variants): array
    {
        $usages = [];

        preg_match_all('/<([a-zA-Z][\w.]*)([^>]*?)(\/?)>/s', $inner, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = $this->normalizeTag($match[1]);
            $attributes = $this->parseAttributes($match[2]);
            $usage = $this->usageFromAttributes($attributes, $variants);
            $usesPathLength = $usage !== null
                && preg_match('/\bpathLength\s*:/', $variants[$usage['variant']]['body']) === 1;

            $usages[] = [
                'tag' => $tag,
                'attributes' => $attributes,
                'selfClosing' => $match[3] === '/',
                'variant' => $usage['variant'] ?? '',
                'params' => $usage['params'] ?? [],
                'part' => null,
                'usesPathLength' => $usesPathLength,
            ];
        }

        return $usages;
    }

    /**
     * Gives every variant usage a part name. Repeated usages of the same
     * variant with identical arguments share one part (chart-line's two
     * drawing paths); different arguments get letter suffixes (radar blips).
     *
     * @param  list<array{tag: string, attributes: array<string, array{value: string, quoted: bool}>, selfClosing: bool, variant: string, params: array<string, float>, part: string|null, usesPathLength: bool}>  $usages
     * @return list<array{tag: string, attributes: array<string, array{value: string, quoted: bool}>, selfClosing: bool, variant: string, params: array<string, float>, part: string|null, usesPathLength: bool}>
     */
    private function assignParts(array $usages): array
    {
        /** @var array<string, list<int>> $groups */
        $groups = [];

        foreach ($usages as $index => $usage) {
            if ($usage['variant'] !== '') {
                $groups[$usage['variant']][] = $index;
            }
        }

        $used = [];
        $assigned = [];

        foreach ($groups as $variant => $indices) {
            $base = Str::kebab((string) preg_replace('/Variants$/', '', $variant));
            $base = $base === '' ? 'part' : $base;

            /** @var array<string, true> $signatureSet */
            $signatureSet = [];

            foreach ($indices as $index) {
                $signature = json_encode($usages[$index]['params']);
                $signatureSet[$signature === false ? '' : $signature] = true;
            }

            $signatures = array_keys($signatureSet);
            $multiple = count($signatures) > 1;

            foreach ($indices as $index) {
                $signature = json_encode($usages[$index]['params']);
                $signature = $signature === false ? '' : $signature;
                $position = (int) array_search($signature, $signatures, true);
                $part = $multiple ? $base.'-'.chr(97 + $position) : $base;

                if (isset($used[$part]) && $used[$part] !== $variant) {
                    $part .= '-'.(count(array_filter($used, fn (string $item): bool => str_starts_with($item, $base))) + 1);
                }

                $used[$part] = $variant;
                $assigned[$index] = $part;
            }
        }

        $result = [];

        foreach ($usages as $index => $usage) {
            if (isset($assigned[$index])) {
                $usage['part'] = $assigned[$index];
            }

            $result[] = $usage;
        }

        return $result;
    }

    /**
     * @param  list<array{tag: string, attributes: array<string, array{value: string, quoted: bool}>, selfClosing: bool, variant: string, params: array<string, float>, part: string|null, usesPathLength: bool}>  $usages
     */
    private function renderMarkup(string $inner, array $usages): string
    {
        $index = 0;

        $markup = (string) preg_replace_callback(
            '/<([a-zA-Z][\w.]*)([^>]*?)(\/?)>/s',
            function (array $match) use (&$index, $usages): string {
                $usage = $usages[$index];
                $index++;

                return $this->renderTag($match[1], $usage);
            },
            $inner,
        );

        $markup = (string) preg_replace('/<\/m\.([a-zA-Z]+)>/', '</$1>', $markup);
        $markup = (string) preg_replace('/\s+/', ' ', trim($markup));
        $markup = (string) preg_replace('/>\s+</', '><', $markup);

        if (str_contains($markup, '<m.')) {
            throw new RuntimeException('Unconverted motion element left in the markup.');
        }

        return $markup;
    }

    /**
     * @param  array{tag: string, attributes: array<string, array{value: string, quoted: bool}>, selfClosing: bool, variant: string, params: array<string, float>, part: string|null, usesPathLength: bool}  $usage
     */
    private function renderTag(string $rawTag, array $usage): string
    {
        $tag = $this->normalizeTag($rawTag);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            throw new RuntimeException("Unsupported SVG element [{$tag}].");
        }

        $attributes = [];

        if ($usage['part'] !== null) {
            $attributes[] = 'data-part="'.$usage['part'].'"';
        }

        if ($usage['usesPathLength']) {
            $attributes[] = 'pathLength="1"';
            $attributes[] = 'stroke-dasharray="1"';
        }

        $style = $this->parseStyle($usage['attributes']['style'] ?? null);

        if ($style !== null) {
            $attributes[] = 'style="'.$style.'"';
        }

        foreach ($usage['attributes'] as $name => $attribute) {
            if (in_array($name, self::SKIPPED_ATTRIBUTES, true) || str_starts_with($name, 'on')) {
                continue;
            }

            $attributes[] = $this->attributeName($name).'="'.$this->literalValue($attribute['value'], $attribute['quoted']).'"';
        }

        $rendered = '<'.$tag.($attributes === [] ? '' : ' '.implode(' ', $attributes));

        return $usage['selfClosing'] ? $rendered.'/>' : $rendered.'>';
    }

    /**
     * @param  array{value: string, quoted: bool}|null  $attribute
     */
    private function parseStyle(?array $attribute): ?string
    {
        if ($attribute === null || trim($attribute['value']) === '') {
            return null;
        }

        $raw = trim($attribute['value']);

        if (str_starts_with($raw, '{')) {
            [$raw] = $this->readBalanced($raw, 0);
        }

        $styles = [];
        $originX = null;
        $originY = null;

        foreach ($this->entries($raw) as [$key, $value]) {
            $value = $this->literalValue($value, false);

            if ($key === 'originX') {
                $originX = $value;

                continue;
            }

            if ($key === 'originY') {
                $originY = $value;

                continue;
            }

            $styles[] = Str::kebab($key).': '.$value;
        }

        if ($originX !== null || $originY !== null) {
            $styles[] = 'transform-origin: '.($originX ?? 'center').' '.($originY ?? 'center');
        }

        return $styles === [] ? null : implode('; ', $styles);
    }

    /**
     * @return array<string, array{value: string, quoted: bool}>
     */
    private function parseAttributes(string $attributes): array
    {
        $parsed = [];
        $length = strlen($attributes);
        $position = 0;

        while ($position < $length) {
            while ($position < $length && ctype_space($attributes[$position])) {
                $position++;
            }

            if ($position >= $length) {
                break;
            }

            $nameStart = $position;

            while ($position < $length && ! ctype_space($attributes[$position]) && $attributes[$position] !== '=') {
                $position++;
            }

            $name = substr($attributes, $nameStart, $position - $nameStart);

            while ($position < $length && ctype_space($attributes[$position])) {
                $position++;
            }

            if ($position >= $length || $attributes[$position] !== '=') {
                $parsed[$name] = ['value' => '', 'quoted' => false];

                continue;
            }

            $position++;

            while ($position < $length && ctype_space($attributes[$position])) {
                $position++;
            }

            if ($position >= $length) {
                break;
            }

            if ($attributes[$position] === '"' || $attributes[$position] === "'") {
                $quote = $attributes[$position];
                $position++;
                $valueStart = $position;

                while ($position < $length && $attributes[$position] !== $quote) {
                    $position++;
                }

                $parsed[$name] = ['value' => substr($attributes, $valueStart, $position - $valueStart), 'quoted' => true];
                $position++;

                continue;
            }

            if ($attributes[$position] === '{') {
                [$value, $position] = $this->readBalanced($attributes, $position);
                $parsed[$name] = ['value' => $value, 'quoted' => false];

                continue;
            }

            $valueStart = $position;

            while ($position < $length && ! ctype_space($attributes[$position])) {
                $position++;
            }

            $parsed[$name] = ['value' => substr($attributes, $valueStart, $position - $valueStart), 'quoted' => false];
        }

        return $parsed;
    }

    private function normalizeTag(string $tag): string
    {
        return str_starts_with($tag, 'm.') ? substr($tag, 2) : $tag;
    }

    private function attributeName(string $name): string
    {
        $map = [
            'strokeWidth' => 'stroke-width',
            'strokeLinecap' => 'stroke-linecap',
            'strokeLinejoin' => 'stroke-linejoin',
            'strokeDasharray' => 'stroke-dasharray',
            'strokeDashoffset' => 'stroke-dashoffset',
            'fillRule' => 'fill-rule',
            'clipRule' => 'clip-rule',
            'fillOpacity' => 'fill-opacity',
            'strokeOpacity' => 'stroke-opacity',
            'pathLength' => 'pathLength',
        ];

        return $map[$name] ?? Str::kebab($name);
    }

    private function literalValue(string $value, bool $quoted): string
    {
        $value = trim($value);

        if ($quoted) {
            return $value;
        }

        if (str_starts_with($value, '"') || str_starts_with($value, "'")) {
            return substr($value, 1, -1);
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1) {
            return $value;
        }

        throw new RuntimeException("Unsupported attribute value [{$value}].");
    }

    /**
     * @param  array<string, float>  $params
     * @param  array<string, array{params: list<string>, body: string}>  $variants
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function variantAnimation(string $variant, array $params, array $variants): array
    {
        if (! isset($variants[$variant])) {
            throw new RuntimeException("Unknown variant [{$variant}].");
        }

        $body = $variants[$variant]['body'];

        $animate = $this->objectProperty($body, 'animate');

        if ($animate === null) {
            throw new RuntimeException("Variant [{$variant}] has no animate state.");
        }

        $arrow = $this->arrowFunction($animate);

        if ($arrow !== null) {
            $animate = $arrow['body'];
        }

        $global = null;
        $perProperty = [];
        $properties = [];

        foreach ($this->entries($animate) as [$key, $value]) {
            if ($key === 'transition') {
                [$global, $perProperty] = $this->parseTransition($value, $params);

                continue;
            }

            if (! in_array($key, self::ANIMATED_PROPERTIES, true) && ! in_array($key, self::TRANSFORM_PROPERTIES, true)) {
                throw new RuntimeException("Unsupported animated property [{$key}].");
            }

            $properties[$key] = $this->parsePropertyValue($value, $params);
        }

        if ($properties === []) {
            throw new RuntimeException("Variant [{$variant}] has no animated properties.");
        }

        if (isset($properties['pathLength'], $properties['strokeDashoffset'])) {
            throw new RuntimeException('pathLength and strokeDashoffset cannot animate together on one element.');
        }

        $tracks = $this->buildTracks($properties, $global ?? $this->defaultTiming(), $perProperty);

        if ($tracks === []) {
            throw new RuntimeException("Variant [{$variant}] did not produce any keyframes.");
        }

        return count($tracks) === 1 ? $tracks[0] : $tracks;
    }

    /**
     * @param  array<string, list<float>>  $properties
     * @param  array{duration: int, delay: int, easing: string, times: list<float>|null}  $global
     * @param  array<string, array{duration: int, delay: int, easing: string, times: list<float>|null}>  $perProperty
     * @return list<array<string, mixed>>
     */
    private function buildTracks(array $properties, array $global, array $perProperty): array
    {
        /** @var array<string, array{values: list<float>, timing: array{duration: int, delay: int, easing: string, times: list<float>|null}}> $transforms */
        $transforms = [];
        /** @var array<string, array{timing: array{duration: int, delay: int, easing: string, times: list<float>|null}, properties: array<string, list<float>>}> $groups */
        $groups = [];

        foreach ($properties as $name => $values) {
            $timing = $perProperty[$name] ?? $global;

            if (in_array($name, self::TRANSFORM_PROPERTIES, true)) {
                $transforms[$name] = ['values' => $values, 'timing' => $timing];

                continue;
            }

            $fingerprint = $this->timingKey($timing);
            $groups[$fingerprint] ??= ['timing' => $timing, 'properties' => []];
            $groups[$fingerprint]['properties'][$name] = $values;
        }

        $tracks = [];

        if ($transforms !== []) {
            $fingerprints = array_unique(array_map(
                fn (array $transform): string => $this->timingKey($transform['timing']),
                $transforms,
            ));

            // Different transform properties can have their own timings
            // (map-pin). Normalise them onto one linear track instead of
            // refusing the icon.
            $track = count($fingerprints) > 1
                ? $this->buildCombinedTransformTrack($transforms)
                : $this->buildTrack(
                    array_map(fn (array $transform): array => $transform['values'], $transforms),
                    reset($transforms)['timing'],
                    true,
                );

            if ($track !== null) {
                $tracks[] = $track;
            }
        }

        foreach ($groups as $group) {
            $track = $this->buildTrack($group['properties'], $group['timing'], false);

            if ($track !== null) {
                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    /**
     * @param  array<string, list<float>>  $properties
     * @param  array{duration: int, delay: int, easing: string, times: list<float>|null}  $timing
     * @return array<string, mixed>|null
     */
    private function buildTrack(array $properties, array $timing, bool $transform): ?array
    {
        $properties = array_filter(
            $properties,
            fn (array $values): bool => count(array_unique($values)) > 1,
        );

        if ($properties === []) {
            return null;
        }

        $maxLength = max(array_map('count', $properties));
        $offsets = $timing['times'] !== null && count($timing['times']) === $maxLength
            ? $timing['times']
            : $this->evenOffsets($maxLength);

        $keyframes = [];

        foreach ($offsets as $offset) {
            $samples = [];

            foreach ($properties as $name => $values) {
                $samples[$name] = $this->sample($values, $timing['times'], $offset);
            }

            $keyframe = ['offset' => $this->round($offset)];

            if ($transform) {
                $keyframe['transform'] = $this->composeTransform($samples);
            } else {
                foreach ($samples as $name => $value) {
                    $key = match ($name) {
                        'pathLength' => 'stroke-dashoffset',
                        'strokeDashoffset' => 'stroke-dashoffset',
                        default => Str::kebab($name),
                    };

                    $keyframe[$key] = $name === 'pathLength' ? $this->round(1 - $value) : $this->round($value);
                }
            }

            $keyframes[] = $keyframe;
        }

        $track = [
            'keyframes' => $keyframes,
            'duration' => $timing['duration'],
            'easing' => $timing['easing'],
        ];

        if ($timing['delay'] > 0) {
            $track['delay'] = $timing['delay'];
        }

        return $track;
    }

    /**
     * Per-property transform timings (map-pin's y and rotate) cannot share a
     * Web Animation, so they are sampled onto one linear track. This trades
     * each property's easing curve for correct timing and values.
     *
     * @param  array<string, array{values: list<float>, timing: array{duration: int, delay: int, easing: string, times: list<float>|null}}>  $transforms
     * @return array<string, mixed>|null
     */
    private function buildCombinedTransformTrack(array $transforms): ?array
    {
        /** @var array<string, list<array{time: float, value: float}>> $framesByProperty */
        $framesByProperty = [];

        foreach ($transforms as $name => $transform) {
            if (count(array_unique($transform['values'])) <= 1) {
                continue;
            }

            $timing = $transform['timing'];
            $values = $transform['values'];
            $count = count($values);
            $positions = $timing['times'] !== null && count($timing['times']) === $count
                ? $timing['times']
                : $this->evenOffsets($count);

            $frames = [];

            foreach ($positions as $index => $position) {
                $frames[] = [
                    'time' => (float) $timing['delay'] + $position * $timing['duration'],
                    'value' => $values[$index] ?? $values[$count - 1],
                ];
            }

            $framesByProperty[$name] = $frames;
        }

        if ($framesByProperty === []) {
            return null;
        }

        $total = 0.0;

        foreach ($framesByProperty as $frames) {
            $last = $frames[count($frames) - 1] ?? null;

            if ($last !== null) {
                $total = max($total, $last['time']);
            }
        }

        if ($total <= 0.0) {
            return null;
        }

        $times = [0.0, $total];

        foreach ($framesByProperty as $frames) {
            foreach ($frames as $frame) {
                $times[] = $frame['time'];
            }
        }

        $times = array_values(array_unique(array_map(fn (float $time): float => $this->round($time), $times)));
        sort($times);

        $keyframes = [];

        foreach ($times as $time) {
            $samples = [];

            foreach ($framesByProperty as $name => $frames) {
                $samples[$name] = $this->valueAt($frames, $time);
            }

            $keyframes[] = [
                'offset' => $this->round($time / $total),
                'transform' => $this->composeTransform($samples),
            ];
        }

        return [
            'keyframes' => $keyframes,
            'duration' => (int) round($total),
            'easing' => 'linear',
        ];
    }

    /**
     * @param  list<array{time: float, value: float}>  $frames
     */
    private function valueAt(array $frames, float $time): float
    {
        $first = $frames[0] ?? null;

        if ($first === null) {
            return 0.0;
        }

        if ($time <= $first['time']) {
            return $first['value'];
        }

        $previous = $first;

        foreach ($frames as $frame) {
            if ($time <= $frame['time']) {
                $span = $frame['time'] - $previous['time'];

                if ($span <= 0.0) {
                    return $frame['value'];
                }

                $progress = ($time - $previous['time']) / $span;

                return $previous['value'] + ($frame['value'] - $previous['value']) * $progress;
            }

            $previous = $frame;
        }

        return $previous['value'];
    }

    /**
     * @param  array<string, float>  $samples
     */
    private function composeTransform(array $samples): string
    {
        $parts = [];

        $x = $samples['translateX'] ?? $samples['x'] ?? null;
        $y = $samples['translateY'] ?? $samples['y'] ?? null;
        $rotate = $samples['rotate'] ?? null;
        $scale = $samples['scale'] ?? null;
        $scaleX = $samples['scaleX'] ?? null;
        $scaleY = $samples['scaleY'] ?? null;

        if ($x !== null) {
            $parts[] = 'translateX('.$this->round($x).'px)';
        }

        if ($y !== null) {
            $parts[] = 'translateY('.$this->round($y).'px)';
        }

        if ($rotate !== null) {
            $parts[] = 'rotate('.$this->round($rotate).'deg)';
        }

        if ($scale !== null) {
            $parts[] = 'scale('.$this->round($scale).')';
        } elseif ($scaleX !== null || $scaleY !== null) {
            $parts[] = 'scale('.$this->round($scaleX ?? 1).', '.$this->round($scaleY ?? 1).')';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  list<float>  $values
     * @param  list<float>|null  $times
     */
    private function sample(array $values, ?array $times, float $offset): float
    {
        if ($values === []) {
            return 0.0;
        }

        $count = count($values);

        if ($count === 1) {
            return $values[0];
        }

        $positions = $times !== null && count($times) === $count ? $times : $this->evenOffsets($count);

        if (count($positions) !== $count) {
            $positions = $this->evenOffsets($count);
        }

        $offset = max(0.0, min(1.0, $offset));
        $previousPosition = 0.0;
        $previousValue = $values[0];

        foreach ($positions as $index => $position) {
            if ($offset <= $position) {
                if ($index === 0) {
                    return $values[0];
                }

                $span = $position - $previousPosition;

                if ($span <= 0.0) {
                    return $values[$index] ?? $previousValue;
                }

                $progress = ($offset - $previousPosition) / $span;

                return $previousValue + (($values[$index] ?? $previousValue) - $previousValue) * $progress;
            }

            $previousPosition = $position;
            $previousValue = $values[$index] ?? $previousValue;
        }

        return $previousValue;
    }

    /**
     * @return list<float>
     */
    private function evenOffsets(int $count): array
    {
        if ($count <= 1) {
            return [0.0];
        }

        $offsets = [];

        for ($index = 0; $index < $count; $index++) {
            $offsets[] = $this->round($index / ($count - 1));
        }

        return $offsets;
    }

    /**
     * @param  array<string, float>  $params
     * @return array{0: array{duration: int, delay: int, easing: string, times: list<float>|null}|null, 1: array<string, array{duration: int, delay: int, easing: string, times: list<float>|null}>}
     */
    private function parseTransition(string $raw, array $params): array
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{')) {
            [$raw] = $this->readBalanced($raw, 0);
        }

        $entries = $this->entries($raw);
        $hasTiming = false;

        foreach ($entries as [$key]) {
            if (in_array($key, self::TIMING_KEYS, true)) {
                $hasTiming = true;
            }
        }

        if ($hasTiming) {
            $perProperty = [];

            foreach ($entries as [$key, $value]) {
                if (! in_array($key, self::TIMING_KEYS, true)) {
                    $perProperty[$key] = $this->parseTiming($value, $params);
                }
            }

            return [$this->parseTimingEntries($entries, $params), $perProperty];
        }

        $perProperty = [];

        foreach ($entries as [$key, $value]) {
            $perProperty[$key] = $this->parseTiming($value, $params);
        }

        return [null, $perProperty];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $entries
     * @param  array<string, float>  $params
     * @return array{duration: int, delay: int, easing: string, times: list<float>|null}
     */
    private function parseTimingEntries(array $entries, array $params): array
    {
        $timing = $this->defaultTiming();

        foreach ($entries as [$key, $value]) {
            if ($key === 'duration') {
                $timing['duration'] = (int) round($this->evaluate($value, $params) * 1000);
            } elseif ($key === 'delay') {
                $timing['delay'] = (int) round($this->evaluate($value, $params) * 1000);
            } elseif ($key === 'ease') {
                $timing['easing'] = $this->easing($value);
            } elseif ($key === 'times') {
                $timing['times'] = $this->numberList($value, $params);
            } elseif ($key === 'type') {
                throw new RuntimeException('Spring transitions are not supported.');
            }
        }

        return $timing;
    }

    /**
     * @param  array<string, float>  $params
     * @return array{duration: int, delay: int, easing: string, times: list<float>|null}
     */
    private function parseTiming(string $raw, array $params): array
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{')) {
            [$raw] = $this->readBalanced($raw, 0);
        }

        return $this->parseTimingEntries($this->entries($raw), $params);
    }

    /**
     * @return array{duration: int, delay: int, easing: string, times: list<float>|null}
     */
    private function defaultTiming(): array
    {
        return ['duration' => 600, 'delay' => 0, 'easing' => 'ease-in-out', 'times' => null];
    }

    private function easing(string $raw): string
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '[')) {
            $values = $this->numberList($raw, []);

            if (count($values) === 4) {
                return 'cubic-bezier('.implode(', ', array_map(fn (float $value): string => $this->number($value), $values)).')';
            }
        }

        $raw = trim($raw, '"\'');
        $map = [
            'easeInOut' => 'ease-in-out',
            'easeOut' => 'ease-out',
            'easeIn' => 'ease-in',
            'linear' => 'linear',
        ];

        return $map[$raw] ?? $raw;
    }

    /**
     * @param  array<string, float>  $params
     */
    private function evaluate(string $expression, array $params = []): float
    {
        $expression = trim($expression);

        uksort($params, fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($params as $name => $value) {
            $expression = (string) preg_replace('/\b'.preg_quote($name, '/').'\b/', $this->number($value), $expression);
        }

        $expression = (string) preg_replace('/\bduration\b/', '1', $expression);
        $expression = (string) preg_replace('/\s+/', '', $expression);

        if ($expression === '' || preg_match('/^[0-9.+\-*\/()]+$/', $expression) !== 1) {
            throw new RuntimeException("Unsupported expression [{$expression}].");
        }

        preg_match_all('/\d+(?:\.\d+)?|[()+\-*\/]/', $expression, $matches);
        $tokens = $matches[0];
        $position = 0;
        $value = $this->parseExpression($tokens, $position);

        if ($position !== count($tokens)) {
            throw new RuntimeException("Unsupported expression [{$expression}].");
        }

        return $value;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseExpression(array $tokens, int &$position): float
    {
        $value = $this->parseTerm($tokens, $position);

        while (in_array($tokens[$position] ?? null, ['+', '-'], true)) {
            $operator = $tokens[$position];
            $position++;
            $right = $this->parseTerm($tokens, $position);
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseTerm(array $tokens, int &$position): float
    {
        $value = $this->parseFactor($tokens, $position);

        while (in_array($tokens[$position] ?? null, ['*', '/'], true)) {
            $operator = $tokens[$position];
            $position++;
            $right = $this->parseFactor($tokens, $position);
            $value = $operator === '*' ? $value * $right : $value / $right;
        }

        return $value;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function parseFactor(array $tokens, int &$position): float
    {
        $token = $tokens[$position] ?? null;

        if ($token === null) {
            throw new RuntimeException('Unexpected end of expression.');
        }

        if ($token === '-' || $token === '+') {
            $position++;
            $value = $this->parseFactor($tokens, $position);

            return $token === '-' ? -$value : $value;
        }

        if ($token === '(') {
            $position++;
            $value = $this->parseExpression($tokens, $position);

            if (($tokens[$position] ?? null) !== ')') {
                throw new RuntimeException('Unclosed expression.');
            }

            $position++;

            return $value;
        }

        if (! is_numeric($token)) {
            throw new RuntimeException("Unsupported token [{$token}].");
        }

        $position++;

        return (float) $token;
    }

    /**
     * @param  array<string, float>  $params
     * @return list<float>
     */
    private function numberList(string $raw, array $params): array
    {
        $raw = trim($raw);

        if (! str_starts_with($raw, '[')) {
            throw new RuntimeException("Expected a number list, got [{$raw}].");
        }

        [$inner] = $this->readBalanced($raw, 0, '[', ']');

        return array_map(fn (string $value): float => $this->evaluate($value, $params), $this->splitTopLevel($inner));
    }

    /**
     * @param  array<string, float>  $params
     * @return list<float>
     */
    private function parsePropertyValue(string $raw, array $params): array
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '[')) {
            return $this->numberList($raw, $params);
        }

        return [$this->evaluate($raw, $params)];
    }

    /**
     * @param  array{duration: int, delay: int, easing: string, times: list<float>|null}  $timing
     */
    private function timingKey(array $timing): string
    {
        return implode('|', [
            $timing['duration'],
            $timing['delay'],
            $timing['easing'],
            $timing['times'] === null ? '' : implode(',', $timing['times']),
        ]);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function entries(string $body): array
    {
        $entries = [];

        foreach ($this->splitTopLevel($body) as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            $colon = $this->firstTopLevelColon($entry);

            if ($colon === null) {
                continue;
            }

            $entries[] = [trim(substr($entry, 0, $colon)), trim(substr($entry, $colon + 1))];
        }

        return $entries;
    }

    private function objectProperty(string $body, string $property): ?string
    {
        foreach ($this->entries($body) as [$key, $value]) {
            if ($key !== $property) {
                continue;
            }

            $value = trim($value);

            if (str_starts_with($value, '{')) {
                [$value] = $this->readBalanced($value, 0);
            }

            return $value;
        }

        return null;
    }

    private function firstTopLevelColon(string $value): ?int
    {
        $length = strlen($value);
        $depth = 0;
        $quote = null;

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'" || $character === '`') {
                $quote = $character;

                continue;
            }

            if (str_contains('{[(', $character)) {
                $depth++;

                continue;
            }

            if (str_contains('}])', $character)) {
                $depth--;

                continue;
            }

            if ($character === ':' && $depth === 0) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function splitTopLevel(string $value): array
    {
        $parts = [];
        $length = strlen($value);
        $depth = 0;
        $quote = null;
        $start = 0;

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'" || $character === '`') {
                $quote = $character;

                continue;
            }

            if (str_contains('{[(', $character)) {
                $depth++;

                continue;
            }

            if (str_contains('}])', $character)) {
                $depth--;

                continue;
            }

            if ($character === ',' && $depth === 0) {
                $parts[] = trim(substr($value, $start, $index - $start));
                $start = $index + 1;
            }
        }

        $parts[] = trim(substr($value, $start));

        return array_values(array_filter($parts, fn (string $part): bool => $part !== ''));
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function readBalanced(string $text, int $openPosition, string $open = '{', string $close = '}'): array
    {
        if (($text[$openPosition] ?? '') !== $open) {
            throw new RuntimeException("Expected [{$open}].");
        }

        $length = strlen($text);
        $depth = 1;
        $quote = null;

        for ($index = $openPosition + 1; $index < $length; $index++) {
            $character = $text[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'" || $character === '`') {
                $quote = $character;

                continue;
            }

            if ($character === $open) {
                $depth++;
            } elseif ($character === $close) {
                $depth--;

                if ($depth === 0) {
                    return [substr($text, $openPosition + 1, $index - $openPosition - 1), $index + 1];
                }
            }
        }

        throw new RuntimeException("Unclosed [{$open}].");
    }

    private function round(float $value): float
    {
        $rounded = round($value, 4);

        return $rounded === -0.0 ? 0.0 : $rounded;
    }

    private function number(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

        return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
    }
}
