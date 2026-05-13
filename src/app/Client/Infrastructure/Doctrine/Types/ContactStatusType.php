<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\ContactStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ContactStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'contact_status';
    }

    protected function getEnumClass(): string
    {
        return ContactStatus::class;
    }
}
