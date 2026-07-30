<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;

final class QuickTemplateIdType extends AbstractUlidType
{
    public function getName(): string { return 'quick_template_id'; }
    protected function getValueObjectClass(): string { return QuickTemplateIdValueObject::class; }
}
