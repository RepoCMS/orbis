<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRepairs;

use App\Inventory\Application\Query\Response\RepairResponse;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;

final readonly class ListRepairsHandler
{
    public function __construct(
        private RepairRepositoryInterface $repairRepository,
    ) {
    }

    /** @return RepairResponse[] */
    public function __invoke(?string $status = null): array
    {
        if ($status !== null) {
            $repairs = $this->repairRepository->findByStatus(RepairStatus::from($status));
        } else {
            $repairs = array_merge(
                $this->repairRepository->findByStatus(RepairStatus::PENDING),
                $this->repairRepository->findByStatus(RepairStatus::IN_PROGRESS),
            );
        }

        return array_map(fn ($r) => RepairResponse::fromEntity($r), $repairs);
    }
}
