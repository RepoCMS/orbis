<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;

/**
 * Domain Service que retorna el árbol de módulos correspondiente a un modelo de ATM.
 *
 * Primero intenta cargar desde la base de datos (si hay nodos seedeados o creados
 * por admin). Si la BD está vacía para el modelo solicitado, cae al árbol
 * hardcodeado definido en AtmModuleTreeData.
 */
final readonly class AtmModuleTreeRegistry
{
    public function __construct(
        private ModuleNodeRepositoryInterface $moduleNodeRepository,
    ) {}

    /**
     * Obtiene el árbol completo de módulos para un modelo de ATM específico.
     *
     * @return AtmModuleNode[]
     */
    public function getTree(AtmModel $model): array
    {
        $dbNodes = $this->moduleNodeRepository->findRootsByModel($model);

        if ($dbNodes !== []) {
            return $this->buildTreeFromDb($dbNodes);
        }

        return $this->buildHardcodedTree($model);
    }

    /**
     * @return AtmModuleNode[]
     */
    private function buildHardcodedTree(AtmModel $model): array
    {
        $tree = AtmModuleTreeData::commonModules();

        if ($model->isRecycler()) {
            $tree[] = AtmModuleTreeData::recyclerModules();
            $tree[] = AtmModuleTreeData::documentDepositModules();
        } else {
            $tree[] = AtmModuleTreeData::dispenserModules();
        }

        return $tree;
    }

    /**
     * @param \App\Support\Domain\Entities\ModuleNode[] $roots
     * @return AtmModuleNode[]
     */
    private function buildTreeFromDb(array $roots): array
    {
        $nodes = [];
        foreach ($roots as $root) {
            $nodes[] = $this->entityToNode($root);
        }
        return $nodes;
    }

    private function entityToNode(\App\Support\Domain\Entities\ModuleNode $entity): AtmModuleNode
    {
        $children = [];
        foreach ($entity->children() as $child) {
            $children[] = $this->entityToNode($child);
        }

        return new AtmModuleNode(
            code: $entity->code(),
            label: $entity->label(),
            children: $children,
            components: $entity->components(),
        );
    }
}
