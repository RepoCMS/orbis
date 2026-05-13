<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\SiteStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class SiteStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'site_status';
    }

    protected function getEnumClass(): string
    {
        return SiteStatus::class;
    }
}
