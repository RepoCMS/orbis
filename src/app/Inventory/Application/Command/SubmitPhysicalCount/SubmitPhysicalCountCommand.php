<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitPhysicalCount;

final readonly class SubmitPhysicalCountCommand
{
    public function __construct(
        public string $countId,
    ) {
    }
}
