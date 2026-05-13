<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\Journey;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface JourneyRepositoryInterface
{
    public function findById(JourneyIdValueObject $id): ?Journey;

    /** @return Journey[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(Journey $journey): void;
}
