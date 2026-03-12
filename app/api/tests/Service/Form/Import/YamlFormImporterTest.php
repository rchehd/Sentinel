<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Import;

use App\Service\Form\Import\YamlFormImporter;
use PHPUnit\Framework\TestCase;

final class YamlFormImporterTest extends TestCase
{
    private YamlFormImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new YamlFormImporter();
    }

    public function testGetFormat(): void
    {
        $this->assertSame('yaml', $this->importer->getFormat());
    }

    public function testGetFileExtensions(): void
    {
        $this->assertSame(['yaml', 'yml'], $this->importer->getFileExtensions());
    }

    public function testDecodeValidYaml(): void
    {
        $content = "title: My YAML Form\nstatus: draft\n";

        $result = $this->importer->decode($content);

        $this->assertIsArray($result);
        $this->assertSame('My YAML Form', $result['title']);
        $this->assertSame('draft', $result['status']);
    }

    public function testDecodeInvalidYamlThrows(): void
    {
        // Indentation error — invalid YAML
        $this->expectException(\RuntimeException::class);

        $this->importer->decode("key:\n  - valid\n bad_indent: broken: yaml: [[[");
    }

    public function testDecodeNonMappingThrows(): void
    {
        // A plain scalar YAML value is not a mapping
        $this->expectException(\RuntimeException::class);

        $this->importer->decode('"just a string"');
    }

    public function testDecodeNullYamlThrows(): void
    {
        // Empty or pure-null YAML documents
        $this->expectException(\RuntimeException::class);

        $this->importer->decode('~');
    }

    public function testDecodeNestedStructure(): void
    {
        $content = "title: Nested Form\nstages:\n  - id: stage-1\n    elements: []\n";

        $result = $this->importer->decode($content);

        $this->assertSame('Nested Form', $result['title']);
        $this->assertCount(1, $result['stages']);
        $this->assertSame('stage-1', $result['stages'][0]['id']);
    }

    public function testDecodeHandlesMultilineStrings(): void
    {
        $content = "title: |\n  Line one\n  Line two\n";

        $result = $this->importer->decode($content);

        $this->assertStringContainsString('Line one', $result['title']);
        $this->assertStringContainsString('Line two', $result['title']);
    }
}
