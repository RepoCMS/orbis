<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteStockMinimum;

use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;

final readonly class DeleteStockMinimumHandler
{
    public function __construct(
        private StockMinimumRepositoryInterface $stockMinimumRepository,
    ) {
    }

    public function __invoke(DeleteStockMinimumCommand $command): void
    {
        $id = new StockMinimumIdValueObject($command->stockMinimumId);

        if ($this->stockMinimumRepository->findById($id) === null) {
            throw new \LogicException(sprintf('StockMinimum con id "%s" no encontrado.', $command->stockMinimumId));
        }

        $this->stockMinimumRepository->delete($id);
    }
}
