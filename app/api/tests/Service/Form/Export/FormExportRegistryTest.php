<?php

declare(strict_types=1);

namespace App\Tests\Service\Form\Export;

use App\Service\Form\Export\FormExporterInterface;
use App\Service\Form\Export\FormExportRegistry;
use PHPUnit\Framework\TestCase;

final class FormExportRegistryTest extends TestCase
{
    private function makeExporter(string $format): FormExporterInterface
    {
        $exporter = $this->createMock(FormExporterInterface::class);
        $exporter->method('getFormat')->willReturn($format);

        return $exporter;
    }

    private function makeRegistry(FormExporterInterface ...$exporters): FormExportRegistry
    {
        return new FormExportRegistry($exporters);
    }

    public function testGetReturnsRegisteredExporter(): void
    {
        $json = $this->makeExporter('json');
        $registry = $this->makeRegistry($json);

        $this->assertSame($json, $registry->get('json'));
    }

    public function testSupportsReturnsTrueForRegisteredFormat(): void
    {
        $registry = $this->makeRegistry($this->makeExporter('json'), $this->makeExporter('yaml'));

        $this->assertTrue($registry->supports('json'));
        $this->assertTrue($registry->supports('yaml'));
    }

    public function testSupportsReturnsFalseForUnknownFormat(): void
    {
        $registry = $this->makeRegistry($this->makeExporter('json'));

        $this->assertFalse($registry->supports('xml'));
        $this->assertFalse($registry->supports('csv'));
    }

    public function testGetThrowsForUnknownFormat(): void
    {
        $registry = $this->makeRegistry($this->makeExporter('json'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/xml/');

        $registry->get('xml');
    }

    public function testGetSupportedFormats(): void
    {
        $registry = $this->makeRegistry($this->makeExporter('json'), $this->makeExporter('yaml'));

        $formats = $registry->getSupportedFormats();

        $this->assertContains('json', $formats);
        $this->assertContains('yaml', $formats);
        $this->assertCount(2, $formats);
    }

    public function testGetSupportedFormatsEmptyWhenNoExporters(): void
    {
        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->getSupportedFormats());
    }

    public function testGetThrowsMessageListsSupportedFormats(): void
    {
        $registry = $this->makeRegistry($this->makeExporter('json'), $this->makeExporter('yaml'));

        try {
            $registry->get('xml');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('json', $e->getMessage());
            $this->assertStringContainsString('yaml', $e->getMessage());
        }
    }
}
