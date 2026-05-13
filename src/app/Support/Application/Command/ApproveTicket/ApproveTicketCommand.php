<?php

declare(strict_types=1);

namespace App\Support\Application\Command\ApproveTicket;

final readonly class ApproveTicketCommand
{
    public function __construct(
        public string $ticketId,
        public string $approvedBy,
        /** Client code portion for CAT generation, e.g. "CLI" */
        public string $clientCode,
        /** City code portion, e.g. "GYE" */
        public string $cityCode,
        /** Terminal code portion, e.g. "ATM01" */
        public string $terminalCode,
    ) {}
}
