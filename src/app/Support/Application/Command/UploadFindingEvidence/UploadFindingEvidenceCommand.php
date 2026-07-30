<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UploadFindingEvidence;

final readonly class UploadFindingEvidenceCommand
{
    public function __construct(
        public string $findingId,
        public string $filePath,
        public string $originalName,
        public string $mimeType,
        public string $moment,
        public string $uploadedBy,
    ) {}
}
