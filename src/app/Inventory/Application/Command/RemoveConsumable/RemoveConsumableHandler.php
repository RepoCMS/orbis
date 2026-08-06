<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RemoveConsumable;

use App\Inventory\Application\Exception\ConsumableNotFoundException;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class RemoveConsumableHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private ConsumableRepositoryInterface $consumableRepository,
    ) {
    }

    public function __invoke(RemoveConsumableCommand $command): void
    {
        $consumableId = new ConsumableIdValueObject($command->consumableId);

        $consumable = $this->consumableRepository->findById($consumableId)
            ?? throw ConsumableNotFoundException::withId($command->consumableId);

        $this->wrapDbExceptions(function () use ($consumable): void {
            $this->consumableRepository->delete($consumable);
        });
    }
}
