<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListPhysicalCounts;

use App\Inventory\Application\Query\Response\PhysicalCountResponse;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;

final readonly class ListPhysicalCountsHandler
{
    public function __construct(
        private PhysicalCountRepositoryInterface $physicalCountRepository,
    ) {
    }

    /** @return PhysicalCountResponse[] */
    public function __invoke(ListPhysicalCountsQuery $query): array
    {
        if ($query->locationId !== null) {
            $counts = $this->physicalCountRepository->findByLocationId($query->locationId);
        } else {
            $counts = $this->physicalCountRepository->findAll();
        }

        $responses = PhysicalCountResponse::fromEntities($counts);

        if ($query->status !== null) {
            $responses = array_values(array_filter(
                $responses,
                static fn(PhysicalCountResponse $r) => $r->status === $query->status,
            ));
        }

        return $responses;
    }
}
