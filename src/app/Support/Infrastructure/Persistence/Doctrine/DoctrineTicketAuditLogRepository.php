<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Persistence\Doctrine;

use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\Repositories\TicketAuditLogRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTicketAuditLogRepository implements TicketAuditLogRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(TicketAuditLogIdValueObject $id): ?TicketAuditLog
    {
        return $this->entityManager->getRepository(TicketAuditLog::class)->find((string) $id);
    }

    public function findByTicketId(string $ticketId): array
    {
        return $this->entityManager->getRepository(TicketAuditLog::class)
            ->findBy(['ticketId' => $ticketId], ['changedAt' => 'DESC']);
    }

    public function save(TicketAuditLog $auditLog): void
    {
        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }
}
