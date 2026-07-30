<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repositories;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Enums\NotificationStatus;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;

interface NotificationLogRepositoryInterface
{
    public function findById(NotificationLogIdValueObject $id): ?NotificationLog;
    public function findByRecipient(string $recipientType, string $recipientId): array;
    public function findByStatus(NotificationStatus $status): array;
    public function findInbox(string $recipientId, ?bool $unreadOnly = false): array;
    public function findAll(?string $status = null, ?string $recipientType = null, ?string $recipientId = null, ?string $eventType = null, ?string $dateFrom = null, ?string $dateTo = null): array;
    public function countUnread(string $recipientId): int;
    public function save(NotificationLog $log): void;
    public function purgeOlderThan(\DateTimeImmutable $cutoff): int;
}
