<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeleteClient;

final readonly class DeleteClientCommand
{
    public function __construct(public string $clientId)
    {
    }
}
