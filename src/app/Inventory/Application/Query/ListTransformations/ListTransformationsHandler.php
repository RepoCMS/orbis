<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListTransformations;

use App\Inventory\Application\Query\Response\TransformationResponse;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;

final readonly class ListTransformationsHandler
{
    public function __construct(private TransformationRepositoryInterface $transformationRepository)
    {
    }

    /** @return TransformationResponse[] */
    public function __invoke(): array
    {
        return array_map(
            static fn ($t) => TransformationResponse::fromEntity($t),
            $this->transformationRepository->findAll(),
        );
    }
}
