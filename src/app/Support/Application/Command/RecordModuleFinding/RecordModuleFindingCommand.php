<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RecordModuleFinding;

final readonly class RecordModuleFindingCommand
{
    public function __construct(
        public string  $ticketId,
        public string  $modulePath,
        public string  $componentName,
        public string  $findingType,
        public string  $registeredBy,
        public ?string $notes = null,
    ) {}
}
