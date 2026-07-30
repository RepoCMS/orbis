<?php

declare(strict_types=1);

namespace App;

use App\Expense\Infrastructure\Messenger\CheckOverdueCashAdvancesMessage;
use App\Identity\Infrastructure\Messenger\CleanupExpiredRefreshTokensMessage;
use App\Identity\Infrastructure\Messenger\DetectOrphanUsersMessage;
use App\Support\Infrastructure\Messenger\EvaluateScheduledMaintenanceMessage;
use App\Support\Infrastructure\Messenger\MonitorSlaMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)

            ->add(RecurringMessage::cron('0 3 * * *', new CleanupExpiredRefreshTokensMessage()))
            ->add(RecurringMessage::cron('0 3 * * *', new DetectOrphanUsersMessage()))
            ->add(RecurringMessage::cron('0 3 * * *', new CheckOverdueCashAdvancesMessage()))
            ->add(RecurringMessage::cron('*/15 * * * *', new MonitorSlaMessage()))
            ->add(RecurringMessage::cron('0 6 * * *', new EvaluateScheduledMaintenanceMessage()))
        ;
    }
}
