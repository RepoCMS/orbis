<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateModuleFinding;

final readonly class UpdateModuleFindingCommand
{
    public function __construct(
        public string  $findingId,
        public string  $findingType,
        public ?string $notes = null,
    ) {}
}
