<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\RepairEvidence;

final readonly class RepairEvidenceResponse
{
    public function __construct(
        public string $id,
        public string $repairId,
        public string $filePath,
        public string $type,
        public string $typeLabel,
        public string $uploadedBy,
        public ?string $notes,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(RepairEvidence $evidence): self
    {
        $typeLabel = match ($evidence->type()) {
            'photo' => 'Foto',
            'video' => 'Video',
            default => $evidence->type()->value,
        };

        return new self(
            id:         (string) $evidence->id(),
            repairId:   (string) $evidence->repairId(),
            filePath:   $evidence->filePath(),
            type:       $evidence->type()->value,
            typeLabel:  $typeLabel,
            uploadedBy: $evidence->uploadedBy(),
            notes:      $evidence->notes(),
            createdAt:  $evidence->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
