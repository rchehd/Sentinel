<?php

declare(strict_types=1);

namespace App\Service\Form\Export;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Encodes a form export payload to a specific serialisation format.
 *
 * Each implementation handles one format (e.g. JSON, YAML). Register a new
 * format by adding a class that implements this interface — Symfony will pick
 * it up automatically via the `app.form.exporter` tag.
 */
#[AutoconfigureTag('app.form.exporter')]
interface FormExporterInterface
{
    /** Format identifier used in API requests, e.g. 'json' or 'yaml'. */
    public function getFormat(): string;

    /**
     * Encode the export payload to a serialised string.
     *
     * @param array<string, mixed> $payload
     */
    public function encode(array $payload): string;

    /** MIME type for the HTTP Content-Type header. */
    public function getContentType(): string;

    /** File extension without leading dot, e.g. 'json'. */
    public function getFileExtension(): string;
}
