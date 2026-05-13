<?php

declare(strict_types=1);

namespace App\Support\Application\Command\RescheduleTicket;

use App\Support\Domain\Enums\RescheduleRequestedBy;

final readonly class RescheduleTicketCommand
{
    public function __construct(
        public string                 $ticketId,
        public \DateTimeImmutable     $newScheduledAt,
        public string                 $reason,
        public RescheduleRequestedBy  $requestedBy,
        public string                 $rescheduledBy,
        public int                    $slaMaxResponseHours = 0,
    ) {}
}
