<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateContact;

final readonly class UpdateContactCommand
{
    public function __construct(
        public string $contactId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public string $role,
    ) {
    }
}
