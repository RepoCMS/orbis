<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TerminalWarrantyIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'terminal_warranty_id';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalWarrantyIdValueObject::class;
    }
}
