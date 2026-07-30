<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\UpdateNotificationPreference;

use App\Notification\Application\Exception\NotificationPreferenceNotFoundException;
use App\Notification\Domain\Repositories\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;

final readonly class UpdateNotificationPreferenceHandler
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
    ) {}

    public function __invoke(UpdateNotificationPreferenceCommand $command): void
    {
        $id = NotificationPreferenceIdValueObject::fromString($command->preferenceId);

        $preference = $this->preferenceRepository->findById($id);

        if ($preference === null) {
            throw NotificationPreferenceNotFoundException::withId($command->preferenceId);
        }

        $preference->toggle($command->isEnabled);

        $this->preferenceRepository->save($preference);
    }
}
