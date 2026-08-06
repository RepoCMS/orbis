<?php

declare(strict_types=1);

namespace App\Support\Application\Command\UpdateModuleNode;

use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;

final readonly class UpdateModuleNodeHandler
{
    public function __construct(
        private ModuleNodeRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateModuleNodeCommand $command): void
    {
        $id = ModuleNodeIdValueObject::fromString($command->id);
        $node = $this->repository->findById($id);

        if ($node === null) {
            throw ModuleNodeNotFoundException::withId($command->id);
        }

        $node->update(
            label: $command->label,
            components: $command->components,
        );

        $this->repository->save($node);
    }
}
