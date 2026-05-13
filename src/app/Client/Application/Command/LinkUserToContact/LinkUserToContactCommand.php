<?php

declare(strict_types=1);

namespace App\Client\Application\Command\LinkUserToContact;

final readonly class LinkUserToContactCommand
{
    public function __construct(
        public string $contactId,
        public string $userId,
    ) {
    }
}