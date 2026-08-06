<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateEmployee;

final readonly class CreateEmployeeCommand
{
    public function __construct(
        public string $identificationNumber,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public string $hiredAt,
        public string $profile = 'administrative',
    ) {
    }
}
