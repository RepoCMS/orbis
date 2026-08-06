<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Persistence\Doctrine;

use App\Location\Domain\Entities\Region;
use App\Location\Domain\Repositories\RegionRepositoryInterface;
use App\Location\Domain\ValueObjects\RegionIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRegionRepository implements RegionRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(RegionIdValueObject $id): ?Region
    {
        return $this->entityManager->getRepository(Region::class)->find((string) $id);
    }

    public function findByName(string $name): ?Region
    {
        return $this->entityManager->getRepository(Region::class)->findOneBy(['name' => $name]);
    }

    /** @return Region[] */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Region::class)->findBy([], ['name' => 'ASC']);
    }

    public function save(Region $region): void
    {
        $this->entityManager->persist($region);
        $this->entityManager->flush();
    }

    public function delete(RegionIdValueObject $id): void
    {
        $region = $this->findById($id);
        if ($region === null) {
            return;
        }
        $this->entityManager->remove($region);
        $this->entityManager->flush();
    }
}
