<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindMovementById;

use App\Inventory\Application\Query\Response\MovementResponse;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;

final readonly class FindMovementByIdHandler
{
    public function __construct(private MovementRepositoryInterface $movementRepository)
    {
    }

    public function __invoke(string $id): MovementResponse
    {
        $movement = $this->movementRepository->findById(new MovementIdValueObject($id));

        if ($movement === null) {
            throw new \RuntimeException(sprintf('Movimiento con ID "%s" no encontrado.', $id));
        }

        return MovementResponse::fromEntity($movement);
    }
}
