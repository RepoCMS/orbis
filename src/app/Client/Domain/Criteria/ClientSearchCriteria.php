<?php

declare(strict_types=1);

namespace App\Client\Domain\Criteria;

use App\Client\Domain\Enums\ClientStatus;

final class ClientSearchCriteria
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $ruc = null,
        public readonly ?ClientStatus $status = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}
