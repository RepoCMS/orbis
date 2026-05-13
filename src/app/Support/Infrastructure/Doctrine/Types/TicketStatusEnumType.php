<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\TicketStatus;

final class TicketStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_ticket_status';
    }

    protected function getEnumClass(): string
    {
        return TicketStatus::class;
    }
}
