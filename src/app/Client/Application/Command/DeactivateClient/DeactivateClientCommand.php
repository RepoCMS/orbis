<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateClient;

final readonly class DeactivateClientCommand
{
    public function __construct(public string $clientId)
    {
    }
}
