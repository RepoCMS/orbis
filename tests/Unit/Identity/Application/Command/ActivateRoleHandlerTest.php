<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ActivateRole\ActivateRoleCommand;
use App\Identity\Application\Command\ActivateRole\ActivateRoleHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class ActivateRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testActivatesRoleSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );
        $role->deactivate();

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);
        $repo->expects($this->once())->method('save');

        $handler = new ActivateRoleHandler($repo);
        ($handler)(new ActivateRoleCommand(self::ROLE_ID));

        self::assertTrue($role->isActive());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ActivateRoleHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new ActivateRoleCommand(self::ROLE_ID));
    }
}
