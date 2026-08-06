<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Entities\TicketModuleFinding;

/** @see \App\Support\Domain\Entities\TicketModuleFinding */
final readonly class TicketModuleFindingResponse
{
    /**
     * @param FindingEvidenceResponse[] $evidences
     */
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $modulePath,
        public string  $componentName,
        public string  $findingType,
        public ?string $notes,
        public string  $registeredBy,
        public string  $registeredAt,
        public array   $evidences = [],
    ) {
    }

    /**
     * @return FindingEvidenceResponse[]
     */
    public static function fromEntity(TicketModuleFinding $finding): self
    {
        $evidences = array_map(
            fn ($evidence) => FindingEvidenceResponse::fromEntity($evidence),
            $finding->evidences(),
        );

        return new self(
            id: (string) $finding->id(),
            ticketId: (string) $finding->ticketId(),
            modulePath: $finding->modulePath()->path,
            componentName: $finding->componentName(),
            findingType: $finding->findingType()->value,
            notes: $finding->notes(),
            registeredBy: $finding->registeredBy(),
            registeredAt: $finding->registeredAt()->format('Y-m-d H:i'),
            evidences: $evidences,
        );
    }
}
