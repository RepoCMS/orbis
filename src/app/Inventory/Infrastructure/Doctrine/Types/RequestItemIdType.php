<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RequestItemIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'request_item_id';
    }
    protected function getValueObjectClass(): string
    {
        return RequestItemIdValueObject::class;
    }
}
