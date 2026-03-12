<?php

declare(strict_types=1);

namespace App\Service\Form\Export;

/** Serialises form export payloads to pretty-printed JSON. */
final class JsonFormExporter implements FormExporterInterface
{
    public function getFormat(): string
    {
        return 'json';
    }

    public function encode(array $payload): string
    {
        return json_encode($payload, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }
}
