<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ProvisionEmployeeAccess;

interface ProvisionEmployeeAccessHandlerInterface
{
    public function __invoke(ProvisionEmployeeAccessCommand $command): void;
}
