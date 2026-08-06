<?php

declare(strict_types=1);

namespace App\Support\Application\Command\CreateModuleNode;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;

final readonly class CreateModuleNodeHandler
{
    public function __construct(
        private ModuleNodeRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateModuleNodeCommand $command): ModuleNode
    {
        $model = AtmModel::from($command->model);

        $parent = null;
        if ($command->parentId !== null) {
            $parentId = ModuleNodeIdValueObject::fromString($command->parentId);
            $parent = $this->repository->findById($parentId);
            if ($parent === null) {
                throw ModuleNodeNotFoundException::withId($command->parentId);
            }
        }

        $position = $this->repository->getNextPosition(
            $command->parentId !== null ? ModuleNodeIdValueObject::fromString($command->parentId) : null,
            $model,
        );

        $node = ModuleNode::create(
            id: ModuleNodeIdValueObject::fromString($this->idGenerator->generate()),
            model: $model,
            code: $command->code,
            label: $command->label,
            components: $command->components,
            parent: $parent,
            position: $position,
        );

        $this->repository->save($node);

        return $node;
    }
}
