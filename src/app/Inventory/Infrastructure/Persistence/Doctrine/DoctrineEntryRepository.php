<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEntryRepository implements EntryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findById(EntryIdValueObject $id): ?Entry
    {
        return $this->em->find(Entry::class, $id);
    }

    public function findByReference(string $reference): ?Entry
    {
        return $this->em->createQueryBuilder()
            ->select('e')->from(Entry::class, 'e')
            ->where('e.reference = :ref')
            ->setParameter('ref', $reference)
            ->getQuery()->getOneOrNullResult();
    }

    public function findDrafts(): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')->from(Entry::class, 'e')
            ->where('e.status = :status')
            ->setParameter('status', EntryStatus::DRAFT->value)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')->from(Entry::class, 'e')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function save(Entry $entry): void
    {
        $this->em->persist($entry);
        $this->em->flush();
    }
}
