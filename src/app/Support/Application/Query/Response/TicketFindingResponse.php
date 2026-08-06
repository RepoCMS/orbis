<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

/** @see \App\Support\Domain\Entities\TicketFinding */
final readonly class TicketFindingResponse
{
    /** @param TicketSparePartResponse[] $spareParts */
    /** @param TicketPendingSparePartResponse[] $pendingSpareParts */
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public ?string $physicalDocNumber,
        public string  $problemTypeFound,
        public string  $problemDetail,
        public bool    $batteryVoltageOk,
        public string  $solution,
        public string  $registeredBy,
        public array   $spareParts = [],
        public array   $pendingSpareParts = [],
    ) {
    }

    public static function fromEntity(\App\Support\Domain\Entities\TicketFinding $finding): self
    {
        $spareParts = array_map(
            fn ($sp) => TicketSparePartResponse::fromEntity($sp),
            $finding->spareParts(),
        );

        $pendingSpareParts = array_map(
            fn ($psp) => TicketPendingSparePartResponse::fromEntity($psp),
            $finding->pendingSpareParts(),
        );

        return new self(
            id: (string) $finding->id(),
            ticketId: (string) $finding->ticketId(),
            physicalDocNumber: $finding->physicalDocNumber(),
            problemTypeFound: $finding->problemTypeFound(),
            problemDetail: $finding->problemDetail(),
            batteryVoltageOk: $finding->batteryVoltageOk(),
            solution: $finding->solution(),
            registeredBy: $finding->registeredBy(),
            spareParts: $spareParts,
            pendingSpareParts: $pendingSpareParts,
        );
    }
}
