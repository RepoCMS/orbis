<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineStockMinimumRepository implements StockMinimumRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(StockMinimumIdValueObject $id): ?StockMinimum
    {
        return $this->em->find(StockMinimum::class, $id);
    }

    public function findByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): ?StockMinimum {
        return $this->em->createQueryBuilder()
            ->select('s')->from(StockMinimum::class, 's')
            ->where('s.productId = :productId')
            ->andWhere('s.locationId = :locationId')
            ->setParameter('productId', $productId)
            ->setParameter('locationId', $locationId)
            ->getQuery()->getOneOrNullResult();
    }

    public function findByLocation(LocationIdValueObject $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')->from(StockMinimum::class, 's')
            ->where('s.locationId = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')->from(StockMinimum::class, 's')
            ->getQuery()->getResult();
    }

    public function save(StockMinimum $stockMinimum): void
    {
        $this->em->persist($stockMinimum);
        $this->em->flush();
    }

    public function delete(StockMinimumIdValueObject $id): void
    {
        $entity = $this->findById($id);
        if ($entity !== null) {
            $this->em->remove($entity);
            $this->em->flush();
        }
    }
}
