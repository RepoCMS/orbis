<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ContactEmailType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'contact_email';
    }

    protected function getValueObjectClass(): string
    {
        return ContactEmailValueObject::class;
    }
}
