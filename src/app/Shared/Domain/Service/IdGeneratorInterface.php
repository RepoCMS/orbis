<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

interface IdGeneratorInterface
{
    public function generate(): string;
}
