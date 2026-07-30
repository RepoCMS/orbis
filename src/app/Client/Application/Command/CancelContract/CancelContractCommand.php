<?php

declare(strict_types=1);

namespace App\Client\Application\Command\CancelContract;

final readonly class CancelContractCommand
{
    public function __construct(
        public string $contractId,
    ) {
    }
}
