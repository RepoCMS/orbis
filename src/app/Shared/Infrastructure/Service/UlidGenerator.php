<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Service;

use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Uid\Ulid;

final class UlidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
