<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\WriteOffNonserializedStock;

use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;

final readonly class WriteOffNonserializedStockHandler
{
    public function __construct(
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
    ) {
    }

    public function __invoke(WriteOffNonserializedStockCommand $command): void
    {
        $stock = $this->nonserializedStockRepository->findById(
            new NonserializedStockIdValueObject($command->nonserializedStockId),
        );

        if ($stock === null) {
            throw new StockNotFoundException($command->nonserializedStockId);
        }

        match ($command->action) {
            'damage'        => $stock->markDamaged(),
            'decommission'  => $stock->decommission(),
            default         => throw new \InvalidArgumentException(
                sprintf('Acción de baja no reconocida: "%s".', $command->action),
            ),
        };

        $this->nonserializedStockRepository->save($stock);
    }
}
