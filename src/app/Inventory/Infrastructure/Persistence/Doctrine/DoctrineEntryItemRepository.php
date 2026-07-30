<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\EntryItem;
use App\Inventory\Domain\Repositories\EntryItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEntryItemRepository implements EntryItemRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(EntryItemIdValueObject $id): ?EntryItem
    {
        return $this->em->find(EntryItem::class, $id);
    }

    public function findByEntry(EntryIdValueObject $entryId): array
    {
        return $this->em->createQueryBuilder()
            ->select('i')->from(EntryItem::class, 'i')
            ->where('i.entryId = :entryId')
            ->setParameter('entryId', $entryId)
            ->getQuery()->getResult();
    }

    public function save(EntryItem $item): void
    {
        $this->em->persist($item);
        $this->em->flush();
    }

    public function delete(EntryItem $item): void
    {
        $this->em->remove($item);
        $this->em->flush();
    }
}
