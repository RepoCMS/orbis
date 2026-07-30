<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTerminalWarrantyRepository implements TerminalWarrantyRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(TerminalWarranty $warranty): void
    {
        $this->em->persist($warranty);
        $this->em->flush();
    }

    public function findById(TerminalWarrantyIdValueObject $id): ?TerminalWarranty
    {
        return $this->em->find(TerminalWarranty::class, $id);
    }

    /**
     * @return TerminalWarranty[]
     */
    public function findByTerminalId(TerminalIdValueObject $terminalId): array
    {
        return $this->em->createQueryBuilder()
            ->select('w')
            ->from(TerminalWarranty::class, 'w')
            ->where('w.terminalId = :terminalId')
            ->setParameter('terminalId', $terminalId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TerminalWarranty[]
     */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('w')
            ->from(TerminalWarranty::class, 'w')
            ->getQuery()
            ->getResult();
    }
}
