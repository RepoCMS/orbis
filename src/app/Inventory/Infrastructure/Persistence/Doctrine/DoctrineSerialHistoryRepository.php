<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\SerialHistory;
use App\Inventory\Domain\Repositories\SerialHistoryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SerialHistoryIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSerialHistoryRepository implements SerialHistoryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findById(SerialHistoryIdValueObject $id): ?SerialHistory
    {
        return $this->em->find(SerialHistory::class, $id);
    }

    public function findBySerializedStock(SerializedStockIdValueObject $serializedStockId): array
    {
        return $this->em->createQueryBuilder()
            ->select('h')->from(SerialHistory::class, 'h')
            ->where('h.serializedStockId = :stockId')
            ->setParameter('stockId', $serializedStockId)
            ->orderBy('h.changedAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function save(SerialHistory $history): void
    {
        $this->em->persist($history);
        $this->em->flush();
    }
}
