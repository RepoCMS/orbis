<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\ContractStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ContractStatusType extends AbstractStringEnumType
{
    public function getName(): string { return 'contract_status'; }
    protected function getEnumClass(): string { return ContractStatus::class; }
}
