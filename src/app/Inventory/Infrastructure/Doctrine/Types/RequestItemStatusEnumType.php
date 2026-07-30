<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\RequestItemStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class RequestItemStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'request_item_status'; }
    protected function getEnumClass(): string { return RequestItemStatus::class; }
}
