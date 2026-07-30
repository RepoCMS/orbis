<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findByCode(ProductCodeAssignedBySupplierValueObject $code): ?Product
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(Product::class, 'p')
            ->where('p.productCodeBySupplier = :code')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('code', $code, 'product_code')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findById(ProductIdValueObject $id): ?Product
    {
        $product = $this->em->find(Product::class, $id);

        if ($product !== null && $product->isDeleted()) {
            return null;
        }

        return $product;
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function delete(ProductIdValueObject $id): void
    {
        $product = $this->em->find(Product::class, $id);

        if ($product !== null) {
            $product->delete();
            $this->save($product);
        }
    }
}
