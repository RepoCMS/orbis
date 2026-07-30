<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

interface SecurityUserResolverInterface
{
    public function getUserId(): ?string;
}
