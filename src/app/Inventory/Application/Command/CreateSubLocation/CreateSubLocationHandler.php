<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateSubLocation;

use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;


final readonly class CreateSubLocationHandler
{
    public function __construct(
        private SubLocationRepositoryInterface $subLocationRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateSubLocationCommand $command): string
    {
        $id       = new SubLocationIdValueObject($this->idGenerator->generate());
        $type     = SubLocationType::from($command->type);
        $parent   = null;

        if ($command->parentId !== null) {
            $parentId = new SubLocationIdValueObject($command->parentId);
            $parent   = $this->subLocationRepository->findById($parentId);

            if ($parent === null) {
                throw new \InvalidArgumentException('La sub-ubicación padre no existe.');
            }
        }

        $subLocation = \App\Inventory\Domain\Entities\SubLocation::create(
            id:          $id,
            locationId:  $command->locationId,
            type:        $type,
            name:        $command->name,
            code:        $command->code,
            parent:      $parent,
        );

        $this->subLocationRepository->save($subLocation);

        return (string) $id;
    }
}
