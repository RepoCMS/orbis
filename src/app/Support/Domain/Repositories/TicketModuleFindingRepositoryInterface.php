<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface TicketModuleFindingRepositoryInterface
{
    public function findById(TicketModuleFindingIdValueObject $id): ?TicketModuleFinding;

    /**
     * @return TicketModuleFinding[]
     */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(TicketModuleFinding $finding): void;

    public function delete(TicketModuleFinding $finding): void;
}
