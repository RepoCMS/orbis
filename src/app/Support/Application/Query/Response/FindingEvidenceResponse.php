<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

use App\Support\Domain\Entities\FindingEvidence;

/** @see \App\Support\Domain\Entities\FindingEvidence */
final readonly class FindingEvidenceResponse
{
    public function __construct(
        public string $id,
        public string $findingId,
        public string $filePath,
        public string $originalName,
        public string $mimeType,
        public string $moment,
        public string $uploadedBy,
        public string $uploadedAt,
    ) {
    }

    public static function fromEntity(FindingEvidence $evidence): self
    {
        return new self(
            id: (string) $evidence->id(),
            findingId: (string) $evidence->findingId(),
            filePath: $evidence->filePath(),
            originalName: $evidence->originalName(),
            mimeType: $evidence->mimeType(),
            moment: $evidence->moment()->value,
            uploadedBy: $evidence->uploadedBy(),
            uploadedAt: $evidence->uploadedAt()->format('Y-m-d H:i'),
        );
    }
}
