<?php
declare(strict_types=1);
namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EntryStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_entry_status'; }
    protected function getEnumClass(): string { return EntryStatus::class; }
}
