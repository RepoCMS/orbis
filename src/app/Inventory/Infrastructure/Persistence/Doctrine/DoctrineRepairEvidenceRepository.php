<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\RepairEvidence;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRepairEvidenceRepository implements RepairEvidenceRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(RepairEvidenceIdValueObject $id): ?RepairEvidence
    {
        return $this->em->find(RepairEvidence::class, $id);
    }

    public function findByRepairId(RepairIdValueObject $repairId): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')->from(RepairEvidence::class, 'e')
            ->where('e.repairId = :repairId')
            ->setParameter('repairId', $repairId)
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function save(RepairEvidence $evidence): void
    {
        $this->em->persist($evidence);
        $this->em->flush();
    }

    public function delete(RepairEvidence $evidence): void
    {
        $this->em->remove($evidence);
        $this->em->flush();
    }
}
