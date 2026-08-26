<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RenameRole\RenameRoleCommand;
use App\Identity\Application\Command\RenameRole\RenameRoleHandler;
use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class RenameRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testRenamesRoleSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Old Name'),
            new RoleSlugValueObject('old-name'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);
        $repo->method('findByName')->willReturn(null);
        $repo->expects($this->once())->method('save');

        $handler = new RenameRoleHandler($repo);
        ($handler)(new RenameRoleCommand(self::ROLE_ID, 'New Name'));

        self::assertSame('New Name', (string) $role->name());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RenameRoleHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new RenameRoleCommand(self::ROLE_ID, 'New Name'));
    }

    public function testThrowsWhenNameExists(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Old Name'),
            new RoleSlugValueObject('old-name'),
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);
        $repo->method('findByName')->willReturn(
            Role::create(
                new RoleIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA2'),
                new RoleNameValueObject('Existing'),
                new RoleSlugValueObject('existing'),
            ),
        );

        $handler = new RenameRoleHandler($repo);
        $this->expectException(RoleNameAlreadyExistsException::class);
        ($handler)(new RenameRoleCommand(self::ROLE_ID, 'Existing'));
    }
}
