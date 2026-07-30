<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Doctrine;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findById(NotificationLogIdValueObject $id): ?NotificationLog
    {
        return $this->em->find(NotificationLog::class, (string) $id);
    }

    public function findByRecipient(string $recipientType, string $recipientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(NotificationLog::class, 'l')
            ->where('l.recipientType = :recipientType')
            ->andWhere('l.recipientId = :recipientId')
            ->setParameter('recipientType', $recipientType)
            ->setParameter('recipientId', $recipientId)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(\App\Notification\Domain\Enums\NotificationStatus $status): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(NotificationLog::class, 'l')
            ->where('l.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findInbox(string $recipientId, ?bool $unreadOnly = false): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(NotificationLog::class, 'l')
            ->where('l.recipientId = :recipientId')
            ->andWhere('l.channel = :channel')
            ->setParameter('recipientId', $recipientId)
            ->setParameter('channel', NotificationChannel::IN_APP)
            ->orderBy('l.createdAt', 'DESC');

        if ($unreadOnly) {
            $qb->andWhere('l.readAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function findAll(?string $status = null, ?string $recipientType = null, ?string $recipientId = null, ?string $eventType = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(NotificationLog::class, 'l');

        if ($status !== null) {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        if ($recipientType !== null) {
            $qb->andWhere('l.recipientType = :recipientType')
                ->setParameter('recipientType', $recipientType);
        }

        if ($recipientId !== null) {
            $qb->andWhere('l.recipientId = :recipientId')
                ->setParameter('recipientId', $recipientId);
        }

        if ($eventType !== null) {
            $qb->andWhere('l.eventType = :eventType')
                ->setParameter('eventType', $eventType);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('l.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new DateTimeImmutable($dateFrom));
        }

        if ($dateTo !== null) {
            $qb->andWhere('l.createdAt <= :dateTo')
                ->setParameter('dateTo', new DateTimeImmutable($dateTo));
        }

        return $qb->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnread(string $recipientId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(NotificationLog::class, 'l')
            ->where('l.recipientId = :recipientId')
            ->andWhere('l.channel = :channel')
            ->andWhere('l.readAt IS NULL')
            ->setParameter('recipientId', $recipientId)
            ->setParameter('channel', NotificationChannel::IN_APP)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(NotificationLog $log): void
    {
        $this->em->persist($log);
        $this->em->flush();
    }

    public function purgeOlderThan(DateTimeImmutable $cutoff): int
    {
        return (int) $this->em->createQueryBuilder()
            ->delete(NotificationLog::class, 'l')
            ->where('l.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
