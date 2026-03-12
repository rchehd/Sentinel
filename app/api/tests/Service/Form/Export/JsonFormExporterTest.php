<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Export;

use App\Service\Form\Export\JsonFormExporter;
use PHPUnit\Framework\TestCase;

final class JsonFormExporterTest extends TestCase
{
    private JsonFormExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new JsonFormExporter();
    }

    public function testGetFormat(): void
    {
        $this->assertSame('json', $this->exporter->getFormat());
    }

    public function testGetContentType(): void
    {
        $this->assertSame('application/json', $this->exporter->getContentType());
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('json', $this->exporter->getFileExtension());
    }

    public function testEncodeProducesValidJson(): void
    {
        $payload = ['title' => 'Test Form', 'status' => 'draft'];

        $result = $this->exporter->encode($payload);

        $decoded = json_decode($result, true);
        $this->assertNotNull($decoded, 'encode() must produce valid JSON');
        $this->assertSame('Test Form', $decoded['title']);
        $this->assertSame('draft', $decoded['status']);
    }

    public function testEncodeIsFormattedPrettyPrint(): void
    {
        $result = $this->exporter->encode(['title' => 'Test']);

        $this->assertStringContainsString("\n", $result, 'encode() output should contain newlines (pretty-printed)');
    }

    public function testEncodeHandlesNestedStructures(): void
    {
        $payload = [
            'title' => 'Nested Form',
            'stages' => [
                ['id' => 'stage-1', 'elements' => []],
            ],
        ];

        $result = $this->exporter->encode($payload);

        $decoded = json_decode($result, true);
        $this->assertNotNull($decoded);
        $this->assertCount(1, $decoded['stages']);
        $this->assertSame('stage-1', $decoded['stages'][0]['id']);
    }

    public function testEncodeHandlesUnicodeWithoutEscaping(): void
    {
        $payload = ['title' => 'Formulário de Contato'];

        $result = $this->exporter->encode($payload);

        $this->assertStringContainsString('Formulário de Contato', $result, 'Unicode characters should not be escaped');
    }
}
