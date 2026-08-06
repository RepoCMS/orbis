<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketEvidenceResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $filePath,
        public string  $uploadedBy,
        public string  $uploadedAt,
        public string  $visibility,
        public string  $visibilityLabel,
    ) {
    }

    public static function fromEntity(\App\Support\Domain\Entities\TicketEvidence $evidence): self
    {
        $visibility = $evidence->visibility();

        return new self(
            id: (string) $evidence->id(),
            ticketId: (string) $evidence->ticketId(),
            filePath: $evidence->filePath(),
            uploadedBy: $evidence->uploadedBy(),
            uploadedAt: $evidence->uploadedAt()->format('Y-m-d H:i'),
            visibility: $visibility->value,
            visibilityLabel: $visibility->label(),
        );
    }
}
