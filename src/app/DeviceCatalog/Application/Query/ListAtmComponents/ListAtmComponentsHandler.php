<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Application\Query\ListAtmComponents;

use App\DeviceCatalog\Domain\AtmComponentId;
use App\DeviceCatalog\Domain\AtmComponentRepositoryInterface;

final readonly class ListAtmComponentsHandler
{
    public function __construct(private AtmComponentRepositoryInterface $repository) {}

    /** @return AtmComponentResponse[] */
    public function __invoke(ListAtmComponentsQuery $query): array
    {
        $parentId = $query->parentId
            ? AtmComponentId::fromString($query->parentId)
            : null;

        $components = $this->repository->findByParent($parentId);

        return array_map(AtmComponentResponse::fromEntity(...), $components);
    }
}
