<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTerminalAssignmentRepository implements TerminalAssignmentRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(TerminalAssignmentIdValueObject $id): ?TerminalAssignment
    {
        $assignment = $this->em->find(TerminalAssignment::class, $id);

        if ($assignment !== null && $assignment->isDeleted()) {
            return null;
        }

        return $assignment;
    }

    /**
     * @return TerminalAssignment[]
     */
    public function findAll(): array
    {
        return $this->em->createQueryBuilder()
            ->select('ta')
            ->from(TerminalAssignment::class, 'ta')
            ->where('ta.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function save(TerminalAssignment $terminalAssignment): void
    {
        $this->em->persist($terminalAssignment);
        $this->em->flush();
    }
}
