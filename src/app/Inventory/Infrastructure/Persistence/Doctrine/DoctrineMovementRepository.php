<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMovementRepository implements MovementRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(MovementIdValueObject $id): ?Movement
    {
        return $this->em->find(Movement::class, $id);
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('m')
            ->from(Movement::class, 'm')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByProduct(ProductIdValueObject $productId): array
    {
        return $this->em->createQueryBuilder()
            ->select('m')
            ->from(Movement::class, 'm')
            ->where('m.productId = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(MovementStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('m')
            ->from(Movement::class, 'm')
            ->where('m.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Movement $movement): void
    {
        $this->em->persist($movement);
        $this->em->flush();
    }
}
