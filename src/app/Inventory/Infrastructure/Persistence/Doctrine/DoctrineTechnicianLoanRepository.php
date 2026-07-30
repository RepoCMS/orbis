<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTechnicianLoanRepository implements TechnicianLoanRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(TechnicianLoanIdValueObject $id): ?TechnicianLoan
    {
        $loan = $this->em->find(TechnicianLoan::class, $id);

        if ($loan !== null && $loan->isDeleted()) {
            return null;
        }

        return $loan;
    }

    public function findAll(?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('tl')
            ->from(TechnicianLoan::class, 'tl')
            ->where('tl.deletedAt IS NULL');

        if ($status !== null) {
            $qb->andWhere('tl.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('tl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFromTechnician(string $fromTechnicianId): array
    {
        return $this->em->createQueryBuilder()
            ->select('tl')
            ->from(TechnicianLoan::class, 'tl')
            ->where('tl.fromTechnicianId = :fromTechnicianId')
            ->andWhere('tl.deletedAt IS NULL')
            ->setParameter('fromTechnicianId', $fromTechnicianId)
            ->orderBy('tl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByToTechnician(string $toTechnicianId): array
    {
        return $this->em->createQueryBuilder()
            ->select('tl')
            ->from(TechnicianLoan::class, 'tl')
            ->where('tl.toTechnicianId = :toTechnicianId')
            ->andWhere('tl.deletedAt IS NULL')
            ->setParameter('toTechnicianId', $toTechnicianId)
            ->orderBy('tl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TechnicianLoan $technicianLoan): void
    {
        $this->em->persist($technicianLoan);
        $this->em->flush();
    }

    public function delete(TechnicianLoanIdValueObject $id): void
    {
        $loan = $this->em->find(TechnicianLoan::class, $id);

        if ($loan !== null) {
            $loan->delete();
            $this->save($loan);
        }
    }
}
