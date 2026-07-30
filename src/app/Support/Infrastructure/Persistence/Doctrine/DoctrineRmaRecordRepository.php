<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\RmaRecord;
use App\Support\Domain\Repositories\RmaRecordRepositoryInterface;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRmaRecordRepository implements RmaRecordRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(RmaRecordIdValueObject $id): ?RmaRecord
    {
        return $this->em->find(RmaRecord::class, $id);
    }

    public function findByTicketId(string $ticketId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(RmaRecord::class, 'r')
            ->where('r.ticketId = :ticketId')
            ->andWhere('r.deletedAt IS NULL')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(RmaRecord::class, 'r')
            ->where('r.deletedAt IS NULL')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(RmaRecord $record): void
    {
        $this->em->persist($record);
        $this->em->flush();
    }

    public function delete(RmaRecordIdValueObject $id): void
    {
        $record = $this->findById($id);
        if ($record === null) {
            return;
        }
        $record->softDelete();
        $this->em->flush();
    }
}
