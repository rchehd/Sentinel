<?php

declare(strict_types=1);

namespace App\Service\Form\Export;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Collects all FormExporterInterface implementations via the `app.form.exporter`
 * tag and exposes them by format key.
 *
 * To add a new export format, create a class that implements
 * FormExporterInterface — it will be discovered and registered automatically.
 */
final class FormExportRegistry
{
    /** @var array<string, FormExporterInterface> */
    private array $exporters = [];

    /**
     * @param iterable<FormExporterInterface> $exporters
     */
    public function __construct(
        #[AutowireIterator('app.form.exporter')]
        iterable $exporters,
    ) {
        foreach ($exporters as $exporter) {
            $this->exporters[$exporter->getFormat()] = $exporter;
        }
    }

    /**
     * Retrieve the exporter for the given format.
     *
     * @throws \InvalidArgumentException when the format is not supported
     */
    public function get(string $format): FormExporterInterface
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException(\sprintf('No exporter registered for format "%s". Supported: %s.', $format, implode(', ', $this->getSupportedFormats())));
        }

        return $this->exporters[$format];
    }

    public function supports(string $format): bool
    {
        return isset($this->exporters[$format]);
    }

    /** @return string[] */
    public function getSupportedFormats(): array
    {
        return array_keys($this->exporters);
    }
}
