<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ModelPartIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ModelPartIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'model_part_id';
    }

    protected function getValueObjectClass(): string
    {
        return ModelPartIdValueObject::class;
    }
}
