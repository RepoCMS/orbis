<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ReactivateContact;

final readonly class ReactivateContactCommand
{
    public function __construct(public string $contactId)
    {
    }
}