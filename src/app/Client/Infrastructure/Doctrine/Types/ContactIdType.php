<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ContactIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'contact_id';
    }

    protected function getValueObjectClass(): string
    {
        return ContactIdValueObject::class;
    }
}
