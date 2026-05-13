<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSerializedStockRepository implements SerializedStockRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(SerializedStockIdValueObject $id): ?SerializedStock
    {
        $stock = $this->em->find(SerializedStock::class, $id);

        if ($stock !== null && $stock->isDeleted()) {
            return null;
        }

        return $stock;
    }

    public function findBySerial(
        ProductIdValueObject $productId,
        StockSerialNumberValueObject $serialNumber,
    ): ?SerializedStock {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->setParameter('serialNumber', $serialNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProduct(ProductIdValueObject $productId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }

    public function findAvailable(): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.status = :status')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('status', 'available')
            ->orderBy('s.productId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(LocationIdValueObject $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.locationId = :locationId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getResult();
    }

    public function countAvailableByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): int {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(SerializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.locationId = :locationId')
            ->andWhere('s.status = :status')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->setParameter('locationId', $locationId)
            ->setParameter('status', 'available')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(SerializedStock $stock): void
    {
        $this->em->persist($stock);
        $this->em->flush();
    }

    public function delete(SerializedStockIdValueObject $id): void
    {
        $stock = $this->em->find(SerializedStock::class, $id);

        if ($stock !== null) {
            $stock->delete();
            $this->save($stock);
        }
    }
}
