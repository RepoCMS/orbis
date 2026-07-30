<?php

declare(strict_types=1);

namespace App\Notification\Domain\Services;

use App\Notification\Domain\Entities\NotificationLog;

interface ChannelSenderInterface
{
    public function send(NotificationLog $log): void;
}
