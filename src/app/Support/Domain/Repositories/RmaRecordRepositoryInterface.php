<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\RmaRecord;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;

interface RmaRecordRepositoryInterface
{
    public function findById(RmaRecordIdValueObject $id): ?RmaRecord;

    /** @return RmaRecord[] */
    public function findByTicketId(string $ticketId): array;

    /** @return RmaRecord[] */
    public function findAll(): array;

    public function save(RmaRecord $record): void;

    public function delete(RmaRecordIdValueObject $id): void;
}
