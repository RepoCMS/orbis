<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Channel;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Services\ChannelSenderInterface;

final readonly class InAppChannelSender implements ChannelSenderInterface
{
    public function send(NotificationLog $log): void
    {
    }
}
