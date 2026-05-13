<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class SiteIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'site_id';
    }

    protected function getValueObjectClass(): string
    {
        return SiteIdValueObject::class;
    }
}
