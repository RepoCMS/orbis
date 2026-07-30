<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\AtmModelCodeValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class AtmModelCodeType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'atm_model_code';
    }

    protected function getValueObjectClass(): string
    {
        return AtmModelCodeValueObject::class;
    }
}
