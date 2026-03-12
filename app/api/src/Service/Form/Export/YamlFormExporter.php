<?php

declare(strict_types=1);

namespace App\Service\Form\Export;

use Symfony\Component\Yaml\Yaml;

/** Serialises form export payloads to human-readable YAML. */
final class YamlFormExporter implements FormExporterInterface
{
    public function getFormat(): string
    {
        return 'yaml';
    }

    public function encode(array $payload): string
    {
        return Yaml::dump($payload, 5, 2);
    }

    public function getContentType(): string
    {
        return 'application/yaml';
    }

    public function getFileExtension(): string
    {
        return 'yaml';
    }
}
