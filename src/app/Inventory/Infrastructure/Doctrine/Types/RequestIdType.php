<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RequestIdType extends AbstractUlidType
{
    public function getName(): string { return 'request_id'; }
    protected function getValueObjectClass(): string { return RequestIdValueObject::class; }
}
