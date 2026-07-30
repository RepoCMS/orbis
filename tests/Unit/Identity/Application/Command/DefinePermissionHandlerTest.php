<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\DefinePermission\DefinePermissionCommand;
use App\Identity\Application\Command\DefinePermission\DefinePermissionHandler;
use App\Identity\Application\Exception\PermissionKeyAlreadyExistsException;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class DefinePermissionHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testDefinesPermissionSuccessfully(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(null);
        $repo->expects($this->once())->method('save');

        $handler = new DefinePermissionHandler($repo);
        ($handler)(new DefinePermissionCommand(self::PERMISSION_ID, 'role.create', 'Create roles'));

        self::assertTrue(true);
    }

    public function testThrowsWhenKeyExists(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(
            Permission::define(
                new PermissionIdValueObject(self::PERMISSION_ID),
                new PermissionKeyValueObject('role.create'),
                'Create roles',
            ),
        );

        $handler = new DefinePermissionHandler($repo);
        $this->expectException(PermissionKeyAlreadyExistsException::class);
        ($handler)(new DefinePermissionCommand(self::PERMISSION_ID, 'role.create', 'Create roles'));
    }
}
