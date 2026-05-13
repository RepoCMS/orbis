<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateEmployee;

final readonly class UpdateEmployeeCommand
{
    public function __construct(
        public string $employeeId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public string $profile = 'administrative',
    ) {
    }
}
