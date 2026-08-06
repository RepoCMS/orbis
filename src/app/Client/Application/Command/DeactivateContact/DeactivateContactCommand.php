<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateContact;

final readonly class DeactivateContactCommand
{
    public function __construct(public string $contactId)
    {
    }
}
