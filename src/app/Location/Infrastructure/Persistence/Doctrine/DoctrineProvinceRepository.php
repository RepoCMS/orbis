<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Persistence\Doctrine;

use App\Location\Domain\Entities\Province;
use App\Location\Domain\Repositories\ProvinceRepositoryInterface;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use App\Location\Domain\ValueObjects\RegionIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProvinceRepository implements ProvinceRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(ProvinceIdValueObject $id): ?Province
    {
        return $this->entityManager->getRepository(Province::class)->find((string) $id);
    }

    public function findByName(string $name): ?Province
    {
        return $this->entityManager->getRepository(Province::class)->findOneBy(['name' => $name]);
    }

    /** @return Province[] */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Province::class)->findBy([], ['name' => 'ASC']);
    }

    /** @return Province[] */
    public function findByRegion(RegionIdValueObject $regionId): array
    {
        return $this->entityManager->getRepository(Province::class)->findBy(
            ['regionId' => (string) $regionId],
            ['name' => 'ASC'],
        );
    }

    public function save(Province $province): void
    {
        $this->entityManager->persist($province);
        $this->entityManager->flush();
    }

    public function delete(ProvinceIdValueObject $id): void
    {
        $province = $this->findById($id);
        if ($province === null) {
            return;
        }
        $this->entityManager->remove($province);
        $this->entityManager->flush();
    }
}
