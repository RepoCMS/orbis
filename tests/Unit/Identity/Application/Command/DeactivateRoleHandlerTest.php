<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\DeactivateRole\DeactivateRoleCommand;
use App\Identity\Application\Command\DeactivateRole\DeactivateRoleHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use PHPUnit\Framework\TestCase;

final class DeactivateRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testDeactivatesRoleSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);
        $repo->expects($this->once())->method('save');

        $handler = new DeactivateRoleHandler($repo);
        ($handler)(new DeactivateRoleCommand(self::ROLE_ID));

        self::assertFalse($role->isActive());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeactivateRoleHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new DeactivateRoleCommand(self::ROLE_ID));
    }
}
