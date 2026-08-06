<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRepairRepository implements RepairRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findById(RepairIdValueObject $id): ?Repair
    {
        return $this->em->find(Repair::class, $id);
    }

    public function findBySerializedStock(SerializedStockIdValueObject $stockId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')->from(Repair::class, 'r')
            ->where('r.serializedStockId = :stockId')
            ->setParameter('stockId', $stockId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findByStatus(RepairStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')->from(Repair::class, 'r')
            ->where('r.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function save(Repair $repair): void
    {
        $this->em->persist($repair);
        $this->em->flush();
    }
}
