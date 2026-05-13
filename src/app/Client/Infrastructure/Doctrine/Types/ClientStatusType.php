<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\ClientStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ClientStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'client_status';
    }

    protected function getEnumClass(): string
    {
        return ClientStatus::class;
    }
}
