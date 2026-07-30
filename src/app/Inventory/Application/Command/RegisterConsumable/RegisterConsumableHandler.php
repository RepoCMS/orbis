<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterConsumable;

use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\Consumable;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class RegisterConsumableHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private RepairRepositoryInterface $repairRepository,
        private ConsumableRepositoryInterface $consumableRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RegisterConsumableCommand $command): string
    {
        $repairId = new RepairIdValueObject($command->repairId);

        // Validar que la reparación existe
        $this->repairRepository->findById($repairId)
            ?? throw RepairNotFoundException::withId($command->repairId);

        $ulidRaw = $this->idGenerator->generate();

        $this->wrapDbExceptions(function () use ($command, $repairId, $ulidRaw): void {
            $consumable = Consumable::register(
                id:          new ConsumableIdValueObject($ulidRaw),
                repairId:    $repairId,
                productId:   $command->productId,
                productCode: $command->productCode,
                productName: $command->productName,
                quantity:    $command->quantity,
                unitCost:    $command->unitCost,
                notes:       $command->notes,
            );

            $this->consumableRepository->save($consumable);
        });

        return $ulidRaw;
    }
}
