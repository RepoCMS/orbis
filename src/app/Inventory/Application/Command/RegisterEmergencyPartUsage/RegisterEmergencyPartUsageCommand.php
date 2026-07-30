<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterEmergencyPartUsage;

final readonly class RegisterEmergencyPartUsageCommand
{
    public function __construct(
        public string $productCode,
        public string $employeeId,
        public string $serialNumber,
        public string $changedBy,
        public ?string $notes,
    ) {
    }
}
