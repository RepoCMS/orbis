<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ChangePermissionDescription\ChangePermissionDescriptionCommand;
use App\Identity\Application\Command\ChangePermissionDescription\ChangePermissionDescriptionHandler;
use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class ChangePermissionDescriptionHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testChangesDescriptionSuccessfully(): void
    {
        $permission = Permission::define(
            new PermissionIdValueObject(self::PERMISSION_ID),
            new PermissionKeyValueObject('role.create'),
            'Old description',
        );

        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->willReturn($permission);
        $repo->expects($this->once())->method('save');

        $handler = new ChangePermissionDescriptionHandler($repo);
        ($handler)(new ChangePermissionDescriptionCommand(self::PERMISSION_ID, 'New description'));

        self::assertSame('New description', $permission->description());
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ChangePermissionDescriptionHandler($repo);
        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new ChangePermissionDescriptionCommand(self::PERMISSION_ID, 'New description'));
    }
}
