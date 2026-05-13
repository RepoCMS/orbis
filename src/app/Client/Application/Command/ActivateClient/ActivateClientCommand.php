<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ActivateClient;

final readonly class ActivateClientCommand
{
    public function __construct(public string $clientId)
    {
    }
}