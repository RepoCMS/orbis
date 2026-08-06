<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\Consumable;
use App\Inventory\Domain\Repositories\ConsumableRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineConsumableRepository implements ConsumableRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findById(ConsumableIdValueObject $id): ?Consumable
    {
        return $this->em->find(Consumable::class, $id);
    }

    public function findByRepairId(RepairIdValueObject $repairId): array
    {
        return $this->em->createQueryBuilder()
            ->select('c')->from(Consumable::class, 'c')
            ->where('c.repairId = :repairId')
            ->setParameter('repairId', $repairId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function save(Consumable $consumable): void
    {
        $this->em->persist($consumable);
        $this->em->flush();
    }

    public function delete(Consumable $consumable): void
    {
        $this->em->remove($consumable);
        $this->em->flush();
    }
}
