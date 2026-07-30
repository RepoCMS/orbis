<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetAtmModuleTree;

use App\Support\Application\Query\Response\ModuleTreeResponse;
use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\AtmModel\AtmModuleTreeRegistry;
use InvalidArgumentException;

final readonly class GetAtmModuleTreeHandler
{
    public function __construct(
        private AtmModuleTreeRegistry $registry,
    ) {}

    /**
     * @return ModuleTreeResponse[]
     */
    public function __invoke(GetAtmModuleTreeQuery $query): array
    {
        $model = AtmModel::tryFrom($query->model);
        if ($model === null) {
            throw new InvalidArgumentException(
                sprintf('Modelo de ATM "%s" no es válido.', $query->model)
            );
        }

        $nodes = $this->registry->getTree($model);

        return array_map(
            fn ($node) => ModuleTreeResponse::fromNode($node),
            $nodes,
        );
    }
}
