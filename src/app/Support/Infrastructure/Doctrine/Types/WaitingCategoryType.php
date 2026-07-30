<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\WaitingCategory;

final class WaitingCategoryType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_waiting_category';
    }

    protected function getEnumClass(): string
    {
        return WaitingCategory::class;
    }
}
