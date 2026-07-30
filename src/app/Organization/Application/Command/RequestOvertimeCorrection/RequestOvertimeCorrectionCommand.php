<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RequestOvertimeCorrection;

final readonly class RequestOvertimeCorrectionCommand
{
    public function __construct(
        public string $overtimeRecordId,
        public string $reviewedBy,
        public ?string $correctionHint = null,
    ) {}
}
