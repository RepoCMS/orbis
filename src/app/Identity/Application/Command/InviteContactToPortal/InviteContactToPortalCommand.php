<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\InviteContactToPortal;

final readonly class InviteContactToPortalCommand
{
    public function __construct(
        public string $contactId,
    ) {
    }
}
