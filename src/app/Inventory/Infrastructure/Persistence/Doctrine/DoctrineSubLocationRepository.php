<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\SubLocation;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSubLocationRepository implements SubLocationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(SubLocationIdValueObject $id): ?SubLocation
    {
        $subLocation = $this->em->find(SubLocation::class, $id);

        if ($subLocation !== null && $subLocation->isDeleted()) {
            return null;
        }

        return $subLocation;
    }

    public function findByLocationId(string $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('sl')
            ->from(SubLocation::class, 'sl')
            ->where('sl.locationId = :locationId')
            ->andWhere('sl.deletedAt IS NULL')
            ->setParameter('locationId', $locationId)
            ->orderBy('sl.depth', 'ASC')
            ->addOrderBy('sl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByParentId(string $parentId): array
    {
        return $this->em->createQueryBuilder()
            ->select('sl')
            ->from(SubLocation::class, 'sl')
            ->join('sl.parent', 'p')
            ->where('p.id = :parentId')
            ->andWhere('sl.deletedAt IS NULL')
            ->setParameter('parentId', $parentId)
            ->orderBy('sl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRootsByLocationId(string $locationId): array
    {
        return $this->em->createQueryBuilder()
            ->select('sl')
            ->from(SubLocation::class, 'sl')
            ->where('sl.locationId = :locationId')
            ->andWhere('sl.parent IS NULL')
            ->andWhere('sl.deletedAt IS NULL')
            ->setParameter('locationId', $locationId)
            ->orderBy('sl.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(SubLocation $subLocation): void
    {
        $this->em->persist($subLocation);
        $this->em->flush();
    }

    public function delete(SubLocationIdValueObject $id): void
    {
        $subLocation = $this->em->find(SubLocation::class, $id);

        if ($subLocation !== null) {
            $subLocation->delete();
            $this->save($subLocation);
        }
    }
}
