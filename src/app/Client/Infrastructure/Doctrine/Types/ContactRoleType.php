<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ContactRoleType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'contact_role';
    }

    protected function getValueObjectClass(): string
    {
        return ContactRoleValueObject::class;
    }
}
