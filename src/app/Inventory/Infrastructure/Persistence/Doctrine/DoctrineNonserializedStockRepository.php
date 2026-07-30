<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineNonserializedStockRepository implements NonserializedStockRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(NonserializedStockIdValueObject $id): ?NonserializedStock
    {
        $stock = $this->em->find(NonserializedStock::class, $id);

        if ($stock !== null && $stock->isDeleted()) {
            return null;
        }

        return $stock;
    }

    public function findByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): ?NonserializedStock {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(NonserializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.locationId = :locationId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProduct(ProductIdValueObject $productId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(NonserializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(LocationIdValueObject $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(NonserializedStock::class, 's')
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
        $stock = $this->findByProductAndLocation($productId, $locationId);

        if ($stock === null || !$stock->isAvailable()) {
            return 0;
        }

        return $stock->quantity();
    }

    public function findByProductAndLocationAndStatus(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        NonserializedStockStatus $status,
    ): ?NonserializedStock {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(NonserializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.locationId = :locationId')
            ->andWhere('s.status = :status')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('productId', $productId)
            ->setParameter('locationId', $locationId)
            ->setParameter('status', $status->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hardDeleteByProductAndLocationAndStatus(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        NonserializedStockStatus $status,
    ): void {
        $this->em->createQueryBuilder()
            ->delete(NonserializedStock::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.locationId = :locationId')
            ->andWhere('s.status = :status')
            ->setParameter('productId', $productId)
            ->setParameter('locationId', $locationId)
            ->setParameter('status', $status->value)
            ->getQuery()
            ->execute();
    }

    public function remove(NonserializedStock $stock): void
    {
        $this->em->remove($stock);
        $this->em->flush();
    }

    public function save(NonserializedStock $stock): void
    {
        $this->em->persist($stock);
        $this->em->flush();
    }

    public function delete(NonserializedStockIdValueObject $id): void
    {
        $stock = $this->em->find(NonserializedStock::class, $id);

        if ($stock !== null) {
            $stock->delete();
            $this->save($stock);
        }
    }
}
