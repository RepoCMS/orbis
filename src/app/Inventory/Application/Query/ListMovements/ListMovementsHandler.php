<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListMovements;

use App\Inventory\Application\Query\Response\MovementResponse;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;

final readonly class ListMovementsHandler
{
    public function __construct(private MovementRepositoryInterface $movementRepository)
    {
    }

    /** @return MovementResponse[] */
    public function __invoke(): array
    {
        return array_map(
            static fn ($m) => MovementResponse::fromEntity($m),
            $this->movementRepository->findAll(),
        );
    }
}
