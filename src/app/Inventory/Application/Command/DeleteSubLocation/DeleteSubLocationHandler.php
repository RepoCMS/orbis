<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteSubLocation;

use App\Inventory\Application\Exception\SubLocationNotFoundException;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class DeleteSubLocationHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private SubLocationRepositoryInterface $subLocationRepository,
    ) {
    }

    public function __invoke(DeleteSubLocationCommand $command): void
    {
        $id          = new SubLocationIdValueObject($command->id);
        $subLocation = $this->subLocationRepository->findById($id)
            ?? throw SubLocationNotFoundException::withId($command->id);

        $this->wrapDbExceptions(function () use ($subLocation, $id): void {
            $subLocation->delete();
            $this->subLocationRepository->delete($id);
        });
    }
}
