<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\TerminalWarranty;

final readonly class WarrantyResponse
{
    public function __construct(
        public string $id,
        public string $terminalId,
        public string $type,
        public string $startDate,
        public string $endDate,
        public ?string $terms,
        public bool $isExpired,
    ) {
    }

    public static function fromEntity(TerminalWarranty $warranty): self
    {
        return new self(
            id: (string) $warranty->id(),
            terminalId: (string) $warranty->terminalId(),
            type: $warranty->type()->value,
            startDate: $warranty->startDate()->format('Y-m-d'),
            endDate: $warranty->endDate()->format('Y-m-d'),
            terms: $warranty->terms(),
            isExpired: $warranty->isExpired(),
        );
    }
}
