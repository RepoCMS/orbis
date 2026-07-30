<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePhysicalCountRepository implements PhysicalCountRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(PhysicalCountIdValueObject $id): ?PhysicalCount
    {
        $count = $this->em->find(PhysicalCount::class, $id);

        if ($count !== null && $count->isDeleted()) {
            return null;
        }

        return $count;
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('pc')
            ->from(PhysicalCount::class, 'pc')
            ->where('pc.deletedAt IS NULL')
            ->orderBy('pc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByLocationId(string $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('pc')
            ->from(PhysicalCount::class, 'pc')
            ->where('pc.locationId = :locationId')
            ->andWhere('pc.deletedAt IS NULL')
            ->setParameter('locationId', $locationId)
            ->orderBy('pc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(PhysicalCount $physicalCount): void
    {
        $this->em->persist($physicalCount);
        $this->em->flush();
    }

    public function delete(PhysicalCountIdValueObject $id): void
    {
        $count = $this->em->find(PhysicalCount::class, $id);

        if ($count !== null) {
            $count->delete();
            $this->save($count);
        }
    }
}
