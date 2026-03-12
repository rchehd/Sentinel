<?php

declare(strict_types=1);

namespace App\Service\Form\Import;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Decodes a raw import file string to a PHP array.
 *
 * Each implementation handles one format (e.g. JSON, YAML). Register a new
 * format by adding a class that implements this interface — Symfony will pick
 * it up automatically via the `app.form.importer` tag.
 */
#[AutoconfigureTag('app.form.importer')]
interface FormImporterInterface
{
    /** Format identifier used in API requests, e.g. 'json' or 'yaml'. */
    public function getFormat(): string;

    /**
     * Parse the raw file content and return it as a PHP array.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException when the content cannot be parsed
     */
    public function decode(string $content): array;

    /** Accepted file extensions for this format, e.g. ['json']. */
    /** @return string[] */
    public function getFileExtensions(): array;
}
