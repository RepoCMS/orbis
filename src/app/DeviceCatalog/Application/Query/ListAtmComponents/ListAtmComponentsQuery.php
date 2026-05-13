<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Application\Query\ListAtmComponents;

final readonly class ListAtmComponentsQuery
{
    public function __construct(public readonly ?string $parentId = null) {}
}
