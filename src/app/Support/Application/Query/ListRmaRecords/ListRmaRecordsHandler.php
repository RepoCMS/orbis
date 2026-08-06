<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListRmaRecords;

use App\Support\Application\Query\Response\RmaRecordResponse;
use App\Support\Domain\Repositories\RmaRecordRepositoryInterface;

final readonly class ListRmaRecordsHandler
{
    public function __construct(
        private RmaRecordRepositoryInterface $rmaRecordRepo,
    ) {
    }

    /** @return RmaRecordResponse[] */
    public function __invoke(ListRmaRecordsQuery $query): array
    {
        if ($query->ticketId !== null) {
            $records = $this->rmaRecordRepo->findByTicketId($query->ticketId);
        } else {
            $records = $this->rmaRecordRepo->findAll();
        }

        $records = array_filter($records, fn ($r) => !$r->isDeleted());

        return array_map(
            fn ($record) => RmaRecordResponse::fromEntity($record),
            array_values($records),
        );
    }
}
