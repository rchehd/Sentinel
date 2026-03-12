<?php

declare(strict_types=1);

namespace App\Service\Form\Import;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Collects all FormImporterInterface implementations via the `app.form.importer`
 * tag and exposes them by format key.
 *
 * To add a new import format, create a class that implements
 * FormImporterInterface — it will be discovered and registered automatically.
 */
final class FormImportRegistry
{
    /** @var array<string, FormImporterInterface> */
    private array $importers = [];

    /**
     * @param iterable<FormImporterInterface> $importers
     */
    public function __construct(
        #[AutowireIterator('app.form.importer')]
        iterable $importers,
    ) {
        foreach ($importers as $importer) {
            $this->importers[$importer->getFormat()] = $importer;
        }
    }

    /**
     * Retrieve the importer for the given format.
     *
     * @throws \InvalidArgumentException when the format is not supported
     */
    public function get(string $format): FormImporterInterface
    {
        if (!isset($this->importers[$format])) {
            throw new \InvalidArgumentException(\sprintf('No importer registered for format "%s". Supported: %s.', $format, implode(', ', $this->getSupportedFormats())));
        }

        return $this->importers[$format];
    }

    public function supports(string $format): bool
    {
        return isset($this->importers[$format]);
    }

    /** @return string[] */
    public function getSupportedFormats(): array
    {
        return array_keys($this->importers);
    }
}
