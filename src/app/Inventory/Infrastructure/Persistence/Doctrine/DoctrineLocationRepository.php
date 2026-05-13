<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineLocationRepository implements LocationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(LocationIdValueObject $id): ?Location
    {
        $location = $this->em->find(Location::class, $id);

        if ($location !== null && $location->isDeleted()) {
            return null;
        }

        return $location;
    }

    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(Location::class, 'l')
            ->where('l.deletedAt IS NULL')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Location $location): void
    {
        $this->em->persist($location);
        $this->em->flush();
    }

    public function delete(LocationIdValueObject $id): void
    {
        $location = $this->em->find(Location::class, $id);

        if ($location !== null) {
            $location->delete();
            $this->save($location);
        }
    }
}
