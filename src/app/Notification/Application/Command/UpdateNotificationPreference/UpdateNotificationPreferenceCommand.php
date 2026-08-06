<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\UpdateNotificationPreference;

final readonly class UpdateNotificationPreferenceCommand
{
    public function __construct(
        public string $preferenceId,
        public bool $isEnabled,
    ) {
    }
}
