<?php

namespace Tests\Unit;

use App\Services\BestPracticeAnalyzer;
use Tests\TestCase;

class BestPracticeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('de');
    }

    public function test_complete_metadata_produces_no_best_practice_warnings(): void
    {
        $tags = [
            'og:title' => ['A clear title'], 'og:description' => ['A clear description'],
            'og:url' => ['https://demo.test/'], 'og:locale' => ['de_DE'],
            'og:image' => ['https://demo.test/image.png'], 'og:image:type' => ['image/png'],
            'og:image:width' => ['1200'], 'og:image:height' => ['630'],
        ];
        $checks = (new BestPracticeAnalyzer)->analyze($tags, 'https://demo.test/', 'https://demo.test/', ['bytes' => 50000, 'mime' => 'image/png']);
        $this->assertSame([], $checks);
    }

    public function test_editorial_limits_are_only_warnings_and_canonical_conflicts_are_explained(): void
    {
        $checks = (new BestPracticeAnalyzer)->analyze([
            'og:title' => [str_repeat('ü', 61)], 'og:description' => ['<b>Text</b>'],
            'og:url' => ['https://demo.test/home'], 'og:locale' => ['de-DE'],
        ], 'http://demo.test/article', 'https://demo.test/article', null);
        $labels = array_column($checks, 'label');
        $this->assertContains('Textlänge og:title', $labels);
        $this->assertContains('Klartext og:description', $labels);
        $this->assertContains('Canonical und og:url', $labels);
        $this->assertContains('Format og:locale', $labels);
        foreach ($checks as $check) {
            $this->assertSame('warn', $check['status']);
            $this->assertSame('best_practice', $check['category']);
        }
    }

    public function test_image_hints_use_actual_bytes_and_mime_type(): void
    {
        $checks = (new BestPracticeAnalyzer)->analyze([
            'og:image' => ['https://demo.test/image.png'], 'og:image:type' => ['image/jpeg'],
        ], 'https://demo.test/', '', ['bytes' => 1000001, 'mime' => 'image/png']);
        $labels = array_column($checks, 'label');
        $this->assertContains('Bild optimieren', $labels);
        $this->assertContains('Bild-MIME-Typ', $labels);
        $this->assertContains('Bildmetadaten width', $labels);
        $this->assertContains('Canonical-Link', $labels);
    }
}
