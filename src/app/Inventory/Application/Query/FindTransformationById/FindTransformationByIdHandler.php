<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindTransformationById;

use App\Inventory\Application\Query\Response\TransformationResponse;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;

final readonly class FindTransformationByIdHandler
{
    public function __construct(private TransformationRepositoryInterface $transformationRepository)
    {
    }

    public function __invoke(string $id): TransformationResponse
    {
        $t = $this->transformationRepository->findById(new TransformationIdValueObject($id));

        if ($t === null) {
            throw new \RuntimeException(sprintf('Transformación con ID "%s" no encontrada.', $id));
        }

        return TransformationResponse::fromEntity($t);
    }
}
