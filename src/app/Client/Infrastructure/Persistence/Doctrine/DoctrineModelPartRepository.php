<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\ModelPart;
use App\Client\Domain\Repositories\ModelPartRepositoryInterface;
use App\Client\Domain\ValueObjects\ModelPartIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineModelPartRepository implements ModelPartRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(ModelPartIdValueObject $id): ?ModelPart
    {
        $part = $this->em->find(ModelPart::class, $id);

        if ($part !== null && $part->isDeleted()) {
            return null;
        }

        return $part;
    }

    public function findByPartNumber(string $partNumber): ?ModelPart
    {
        return $this->em->createQueryBuilder()
            ->select('mp')
            ->from(ModelPart::class, 'mp')
            ->where('mp.partNumber = :partNumber')
            ->andWhere('mp.deletedAt IS NULL')
            ->setParameter('partNumber', $partNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByModel(string $model): array
    {
        return $this->em->createQueryBuilder()
            ->select('mp')
            ->from(ModelPart::class, 'mp')
            ->where('mp.model = :model')
            ->andWhere('mp.deletedAt IS NULL')
            ->setParameter('model', $model)
            ->orderBy('mp.level', 'ASC')
            ->addOrderBy('mp.partName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ModelPart $part): void
    {
        $this->em->persist($part);
        $this->em->flush();
    }

    public function delete(ModelPartIdValueObject $id): void
    {
        $part = $this->em->find(ModelPart::class, $id);

        if ($part !== null) {
            $part->softDelete();
            $this->save($part);
        }
    }
}
