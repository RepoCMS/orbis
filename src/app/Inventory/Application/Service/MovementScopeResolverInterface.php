<?php

declare(strict_types=1);

namespace App\Inventory\Application\Service;

interface MovementScopeResolverInterface
{
    public function resolve(?string $userId): ?string;
}
