<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterClient;

final readonly class RegisterClientCommand
{
    public function __construct(
        public string $name,
        public string $ruc,
        public ?string $address = null,
    ) {
    }
}
