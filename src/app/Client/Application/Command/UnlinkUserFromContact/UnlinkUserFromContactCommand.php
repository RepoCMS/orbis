<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UnlinkUserFromContact;

final readonly class UnlinkUserFromContactCommand
{
    public function __construct(
        public string $contactId,
    ) {
    }
}
