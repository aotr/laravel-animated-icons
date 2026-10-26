<?php

namespace Aotr\AnimatedIcons\Tests;

use Aotr\AnimatedIcons\Actions\ConvertAnimatedIconSourceAction;
use PHPUnit\Framework\TestCase;

final class ConverterTest extends TestCase
{
    public function test_it_converts_object_variants(): void
    {
        $entry = (new ConvertAnimatedIconSourceAction())->handle($this->source('sprout'));

        $this->assertSame(['grow', 'leaf'], array_keys($entry['animation']));
        $this->assertSame(600, $entry['animation']['grow']['duration']);
        $this->assertSame(700, $entry['animation']['leaf']['duration']);
        $this->assertSame(200, $entry['animation']['leaf']['delay']);
        $this->assertStringContainsString('data-part="grow"', $entry['markup']);
    }

    public function test_it_converts_path_length_to_stroke_dashoffset(): void
    {
        $entry = (new ConvertAnimatedIconSourceAction())->handle($this->source('chart-line'));

        $this->assertSame(['root', 'path'], array_keys($entry['animation']));
        $this->assertStringContainsString('pathLength="1"', $entry['markup']);
        $this->assertSame(1.0, $entry['animation']['path']['keyframes'][0]['stroke-dashoffset']);
    }

    public function test_it_expands_variant_factories(): void
    {
        $entry = (new ConvertAnimatedIconSourceAction())->handle($this->source('radar'));

        $this->assertArrayHasKey('blip-a', $entry['animation']);
        $this->assertArrayHasKey('blip-b', $entry['animation']);
        $this->assertSame(550, $entry['animation']['blip-a'][0]['delay']);
        $this->assertSame(300, $entry['animation']['blip-b'][0]['delay']);
    }

    private function source(string $name): string
    {
        $fixture = json_decode((string) file_get_contents(__DIR__.'/Fixtures/'.$name.'.json'), true);

        return (string) ($fixture['files'][0]['content'] ?? '');
    }
}
