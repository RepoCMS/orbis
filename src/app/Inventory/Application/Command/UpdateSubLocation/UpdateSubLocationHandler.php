<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateSubLocation;

use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class UpdateSubLocationHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private SubLocationRepositoryInterface $subLocationRepository,
    ) {
    }

    public function __invoke(UpdateSubLocationCommand $command): void
    {
        $id          = new SubLocationIdValueObject($command->id);
        $subLocation = $this->subLocationRepository->findById($id)
            ?? throw SubLocationNotFoundException::withId($command->id);

        $this->wrapDbExceptions(function () use ($subLocation, $command): void {
            $subLocation->update($command->name, $command->code);

            $this->subLocationRepository->save($subLocation);
        });
    }
}
