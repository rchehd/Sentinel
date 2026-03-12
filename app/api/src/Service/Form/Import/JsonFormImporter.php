<?php

declare(strict_types=1);

namespace App\Service\Form\Import;

/** Parses JSON form import files. */
final class JsonFormImporter implements FormImporterInterface
{
    public function getFormat(): string
    {
        return 'json';
    }

    public function decode(string $content): array
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Invalid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!\is_array($data)) {
            throw new \RuntimeException('JSON root must be an object.');
        }

        return $data;
    }

    public function getFileExtensions(): array
    {
        return ['json'];
    }
}
