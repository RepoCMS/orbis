<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repositories;

use App\Notification\Domain\Entities\NotificationPreference;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;

interface NotificationPreferenceRepositoryInterface
{
    public function findById(NotificationPreferenceIdValueObject $id): ?NotificationPreference;
    public function findByRecipient(RecipientType $recipientType, string $recipientId): array;
    public function save(NotificationPreference $preference): void;
}
