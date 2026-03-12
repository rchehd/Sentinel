<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Import;

use App\Service\Form\Import\FormImporterInterface;
use App\Service\Form\Import\FormImportRegistry;
use PHPUnit\Framework\TestCase;

final class FormImportRegistryTest extends TestCase
{
    private function makeImporter(string $format): FormImporterInterface
    {
        $importer = $this->createMock(FormImporterInterface::class);
        $importer->method('getFormat')->willReturn($format);

        return $importer;
    }

    private function makeRegistry(FormImporterInterface ...$importers): FormImportRegistry
    {
        return new FormImportRegistry($importers);
    }

    public function testGetReturnsRegisteredImporter(): void
    {
        $json = $this->makeImporter('json');
        $registry = $this->makeRegistry($json);

        $this->assertSame($json, $registry->get('json'));
    }

    public function testSupportsReturnsTrueForRegisteredFormat(): void
    {
        $registry = $this->makeRegistry($this->makeImporter('json'), $this->makeImporter('yaml'));

        $this->assertTrue($registry->supports('json'));
        $this->assertTrue($registry->supports('yaml'));
    }

    public function testSupportsReturnsFalseForUnknownFormat(): void
    {
        $registry = $this->makeRegistry($this->makeImporter('json'));

        $this->assertFalse($registry->supports('xml'));
        $this->assertFalse($registry->supports('csv'));
    }

    public function testGetThrowsForUnknownFormat(): void
    {
        $registry = $this->makeRegistry($this->makeImporter('json'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/xml/');

        $registry->get('xml');
    }

    public function testGetSupportedFormats(): void
    {
        $registry = $this->makeRegistry($this->makeImporter('json'), $this->makeImporter('yaml'));

        $formats = $registry->getSupportedFormats();

        $this->assertContains('json', $formats);
        $this->assertContains('yaml', $formats);
        $this->assertCount(2, $formats);
    }

    public function testGetSupportedFormatsEmptyWhenNoImporters(): void
    {
        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->getSupportedFormats());
    }

    public function testGetThrowsMessageListsSupportedFormats(): void
    {
        $registry = $this->makeRegistry($this->makeImporter('json'), $this->makeImporter('yaml'));

        try {
            $registry->get('xml');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('json', $e->getMessage());
            $this->assertStringContainsString('yaml', $e->getMessage());
        }
    }
}
