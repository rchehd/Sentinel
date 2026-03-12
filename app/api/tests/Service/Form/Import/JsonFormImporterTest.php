<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Import;

use App\Service\Form\Import\JsonFormImporter;
use PHPUnit\Framework\TestCase;

final class JsonFormImporterTest extends TestCase
{
    private JsonFormImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new JsonFormImporter();
    }

    public function testGetFormat(): void
    {
        $this->assertSame('json', $this->importer->getFormat());
    }

    public function testGetFileExtensions(): void
    {
        $this->assertSame(['json'], $this->importer->getFileExtensions());
    }

    public function testDecodeValidJson(): void
    {
        $content = '{"title": "My Form", "status": "draft"}';

        $result = $this->importer->decode($content);

        $this->assertIsArray($result);
        $this->assertSame('My Form', $result['title']);
        $this->assertSame('draft', $result['status']);
    }

    public function testDecodeInvalidJsonThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->importer->decode('not json at all {{{');
    }

    public function testDecodeNonObjectThrows(): void
    {
        // A JSON string at the root level is not an object/array — should be rejected
        $this->expectException(\RuntimeException::class);

        $this->importer->decode('"just a string"');
    }

    public function testDecodeNullJsonThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->importer->decode('null');
    }

    public function testDecodeNestedStructure(): void
    {
        $content = json_encode([
            'title' => 'Complex Form',
            'stages' => [
                ['id' => 'stage-1', 'elements' => []],
            ],
        ], \JSON_THROW_ON_ERROR);

        $result = $this->importer->decode($content);

        $this->assertSame('Complex Form', $result['title']);
        $this->assertCount(1, $result['stages']);
    }

    public function testDecodeEmptyObjectReturnsEmptyArray(): void
    {
        $result = $this->importer->decode('{}');

        $this->assertSame([], $result);
    }
}
