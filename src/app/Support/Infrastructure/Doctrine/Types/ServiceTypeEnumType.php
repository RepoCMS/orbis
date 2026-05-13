<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\ServiceType;

final class ServiceTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_service_type';
    }

    protected function getEnumClass(): string
    {
        return ServiceType::class;
    }
}
