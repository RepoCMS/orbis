<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\GenerateBackupCodes;

final readonly class GenerateBackupCodesCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
