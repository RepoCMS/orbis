<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\EvidenceType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EvidenceTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_evidence_type'; }
    protected function getEnumClass(): string { return EvidenceType::class; }
}
