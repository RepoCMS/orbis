<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContractById;

final readonly class FindContractByIdQuery
{
    public function __construct(
        public string $contractId,
    ) {
    }
}
