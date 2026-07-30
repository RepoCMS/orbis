<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Persistence\Doctrine;

use App\Location\Domain\Entities\City;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\ValueObjects\CityIdValueObject;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCityRepository implements CityRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function findById(CityIdValueObject $id): ?City
    {
        return $this->entityManager->getRepository(City::class)->find((string) $id);
    }

    public function findByName(string $name): ?City
    {
        return $this->entityManager->getRepository(City::class)->findOneBy(['name' => $name]);
    }

    /** @return City[] */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(City::class)->findBy([], ['name' => 'ASC']);
    }

    /** @return City[] */
    public function findByProvince(ProvinceIdValueObject $provinceId): array
    {
        return $this->entityManager->getRepository(City::class)->findBy(
            ['provinceId' => $provinceId],
            ['name' => 'ASC'],
        );
    }

    public function save(City $city): void
    {
        $this->entityManager->persist($city);
        $this->entityManager->flush();
    }

    /** @return City[] */
    public function searchByName(string $term, int $limit = 20): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('c.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function delete(CityIdValueObject $id): void
    {
        $city = $this->findById($id);
        if ($city === null) { return; }
        $this->entityManager->remove($city);
        $this->entityManager->flush();
    }
}