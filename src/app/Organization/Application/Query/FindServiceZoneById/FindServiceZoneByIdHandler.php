<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\FindServiceZoneById;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Application\Query\Response\ServiceZoneResponse;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class FindServiceZoneByIdHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    public function __invoke(FindServiceZoneByIdQuery $query): ServiceZoneResponse
    {
        $zone = $this->repository->findById(new ServiceZoneIdValueObject($query->serviceZoneId))
            ?? throw ServiceZoneNotFoundException::withId($query->serviceZoneId);

        return ServiceZoneResponse::fromEntity($zone);
    }
}
