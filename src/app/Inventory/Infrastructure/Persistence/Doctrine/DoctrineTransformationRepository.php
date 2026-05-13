<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransformationRepository implements TransformationRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(TransformationIdValueObject $id): ?Transformation
    {
        return $this->em->find(Transformation::class, $id);
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')->from(Transformation::class, 't')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findBySerializedStock(SerializedStockIdValueObject $stockId): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')->from(Transformation::class, 't')
            ->where('t.serializedStockId = :stockId')
            ->setParameter('stockId', $stockId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function save(Transformation $transformation): void
    {
        $this->em->persist($transformation);
        $this->em->flush();
    }
}
