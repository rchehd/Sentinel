<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Export;

use App\Service\Form\Export\YamlFormExporter;
use PHPUnit\Framework\TestCase;

final class YamlFormExporterTest extends TestCase
{
    private YamlFormExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new YamlFormExporter();
    }

    public function testGetFormat(): void
    {
        $this->assertSame('yaml', $this->exporter->getFormat());
    }

    public function testGetContentType(): void
    {
        $this->assertSame('application/yaml', $this->exporter->getContentType());
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('yaml', $this->exporter->getFileExtension());
    }

    public function testEncodeProducesValidYaml(): void
    {
        $payload = ['title' => 'Test Form', 'status' => 'draft'];

        $result = $this->exporter->encode($payload);

        $this->assertStringContainsString('title:', $result);
        $this->assertStringContainsString('Test Form', $result);
    }

    public function testEncodeOutputContainsAllKeys(): void
    {
        $payload = ['title' => 'My Form', 'status' => 'published', 'description' => 'A description'];

        $result = $this->exporter->encode($payload);

        $this->assertStringContainsString('title:', $result);
        $this->assertStringContainsString('status:', $result);
        $this->assertStringContainsString('description:', $result);
    }

    public function testEncodeHandlesNestedStructures(): void
    {
        $payload = [
            'title' => 'Nested',
            'stages' => [
                ['id' => 'stage-1', 'elements' => []],
            ],
        ];

        $result = $this->exporter->encode($payload);

        $this->assertStringContainsString('stages:', $result);
        $this->assertStringContainsString('stage-1', $result);
    }
}
