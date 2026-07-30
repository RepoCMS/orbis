<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Entities\ProcessedCommand;
use App\Shared\Domain\Repositories\ProcessedCommandRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProcessedCommandRepository implements ProcessedCommandRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function exists(string $commandType, string $idempotencyKey): bool
    {
        $qb = $this->entityManager->createQueryBuilder();

        $count = (int) $qb->select('COUNT(pc.id)')
            ->from(ProcessedCommand::class, 'pc')
            ->where('pc.commandType = :commandType')
            ->andWhere('pc.idempotencyKey = :idempotencyKey')
            ->setParameter('commandType', $commandType)
            ->setParameter('idempotencyKey', $idempotencyKey)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function save(ProcessedCommand $processedCommand): void
    {
        $this->entityManager->persist($processedCommand);
        $this->entityManager->flush();
    }

    public function trySave(ProcessedCommand $processedCommand): bool
    {
        try {
            $this->entityManager->persist($processedCommand);
            $this->entityManager->flush();

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
