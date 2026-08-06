<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RegisterFinding;

final readonly class RegisterFindingCommand
{
    public function __construct(
        public string  $ticketId,
        public string  $problemTypeFound,
        public string  $problemDetail,
        public bool    $batteryVoltageOk,
        public string  $solution,
        public string  $registeredBy,
        public ?string $physicalDocNumber = null,
    ) {
    }
}
