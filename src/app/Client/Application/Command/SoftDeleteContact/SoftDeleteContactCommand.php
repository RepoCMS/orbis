<?php

declare(strict_types=1);

namespace App\Client\Application\Command\SoftDeleteContact;

final readonly class SoftDeleteContactCommand
{
    public function __construct(public string $contactId)
    {
    }
}