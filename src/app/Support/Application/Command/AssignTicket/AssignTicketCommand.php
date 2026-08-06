<?php

declare(strict_types=1);

namespace App\Support\Application\Command\AssignTicket;

final readonly class AssignTicketCommand
{
    /**
     * @param string[] $employeeIds   All technician ULID IDs
     * @param string   $inChargeId    ULID of the lead technician (must be in $employeeIds)
     */
    public function __construct(
        public string              $ticketId,
        public array               $employeeIds,
        public string              $inChargeId,
        public ?\DateTimeImmutable $scheduledAt,
        public int                 $slaMaxResponseHours = 0,
    ) {
    }
}
