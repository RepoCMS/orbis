<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateClient;

final readonly class UpdateClientCommand
{
    public function __construct(
        public string $clientId,
        public string $name,
        public ?string $address = null,
    ) {
    }
}
