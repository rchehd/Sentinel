<?php

declare(strict_types=1);

namespace App\Service\Form\Import;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/** Parses YAML form import files. */
final class YamlFormImporter implements FormImporterInterface
{
    public function getFormat(): string
    {
        return 'yaml';
    }

    public function decode(string $content): array
    {
        try {
            /** @var array<string, mixed>|null $data */
            $data = Yaml::parse($content);
        } catch (ParseException $e) {
            throw new \RuntimeException('Invalid YAML: ' . $e->getMessage(), 0, $e);
        }

        if (!\is_array($data)) {
            throw new \RuntimeException('YAML root must be a mapping.');
        }

        return $data;
    }

    public function getFileExtensions(): array
    {
        return ['yaml', 'yml'];
    }
}
