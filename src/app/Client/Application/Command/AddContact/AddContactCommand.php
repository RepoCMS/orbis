<?php

declare(strict_types=1);

namespace App\Client\Application\Command\AddContact;

final readonly class AddContactCommand
{
    public function __construct(
        public string $clientId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public string $role,
    ) {
    }
}
