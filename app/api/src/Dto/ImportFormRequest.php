<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Request payload for POST /forms/import. */
class ImportFormRequest
{
    public function __construct(
        /** Raw file content — JSON string or YAML string. */
        #[Assert\NotBlank]
        public readonly string $content = '',

        /** Source format: 'json' or 'yaml'. */
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['json', 'yaml'])]
        public readonly string $format = 'json',
    ) {
    }
}
