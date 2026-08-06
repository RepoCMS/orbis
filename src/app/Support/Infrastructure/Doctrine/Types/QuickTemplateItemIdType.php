<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;

final class QuickTemplateItemIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'quick_template_item_id';
    }
    protected function getValueObjectClass(): string
    {
        return QuickTemplateItemIdValueObject::class;
    }
}
