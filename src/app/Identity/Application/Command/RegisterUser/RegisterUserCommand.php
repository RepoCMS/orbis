<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $username,
        public string $rawPassword,
        public string $roleId,
    ) {
    }
}
