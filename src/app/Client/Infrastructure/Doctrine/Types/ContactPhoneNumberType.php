<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ContactPhoneNumberType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'contact_phone_number';
    }

    protected function getValueObjectClass(): string
    {
        return ContactPhoneNumberValueObject::class;
    }
}
