<?php

declare(strict_types=1);

namespace App\Enum;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Complete = 'complete';
}
