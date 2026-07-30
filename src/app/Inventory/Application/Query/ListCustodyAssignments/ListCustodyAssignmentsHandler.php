<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListCustodyAssignments;

use App\Inventory\Application\Query\Response\CustodyAssignmentResponse;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

final readonly class ListCustodyAssignmentsHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
    ) {
    }

    /** @return CustodyAssignmentResponse[] */
    public function __invoke(ListCustodyAssignmentsQuery $query): array
    {
        if ($query->employeeId !== null) {
            $locations = $this->locationRepository->findByEmployeeId($query->employeeId);
        } elseif ($query->locationId !== null) {
            $location = $this->locationRepository->findById(
                new LocationIdValueObject($query->locationId),
            );

            $locations = $location !== null ? [$location] : [];
        } else {
            $locations = $this->locationRepository->findByType(LocationType::CUSTODY);
        }

        $result = [];

        foreach ($locations as $location) {
            if (!$location->isCustody()) {
                continue;
            }

            $stockItems = $this->serializedStockRepository->findByLocation($location->id());

            foreach ($stockItems as $item) {
                $result[] = CustodyAssignmentResponse::fromEntities($location, $item);
            }
        }

        return $result;
    }
}
