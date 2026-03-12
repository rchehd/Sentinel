<?php

declare(strict_types=1);

namespace App\Dto;

class SaveSchemaRequest
{
    /**
     * @param array<mixed> $schema
     */
    public function __construct(
        public readonly array $schema = [],
    ) {
    }
}
