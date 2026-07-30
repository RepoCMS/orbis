<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\CreateRole\CreateRoleCommand;
use App\Identity\Application\Command\CreateRole\CreateRoleHandler;
use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateRoleHandlerTest extends TestCase
{
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const GENERATED_ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testCreatesRoleSuccessfully(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findByName')->willReturn(null);
        $roleRepo->expects($this->once())->method('save');

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(
            Permission::define(
                new PermissionIdValueObject(self::PERMISSION_ID),
                new PermissionKeyValueObject('role.create'),
                'Create roles',
            ),
        );

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ROLE_ID);

        $handler = new CreateRoleHandler($roleRepo, $permissionRepo, $idGenerator);
        ($handler)(new CreateRoleCommand('Admin', [self::PERMISSION_ID]));

        self::assertTrue(true); // no exception means success
    }

    public function testThrowsWhenNameExists(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findByName')->willReturn(
            \App\Identity\Domain\Entities\Role::create(
                new \App\Identity\Domain\ValueObjects\RoleIdValueObject(self::GENERATED_ROLE_ID),
                new \App\Identity\Domain\ValueObjects\RoleNameValueObject('Admin'),
            ),
        );

        $handler = new CreateRoleHandler(
            $roleRepo,
            $this->createMock(PermissionRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(RoleNameAlreadyExistsException::class);
        ($handler)(new CreateRoleCommand('Admin', []));
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findByName')->willReturn(null);

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(null);

        $handler = new CreateRoleHandler(
            $roleRepo, $permissionRepo,
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new CreateRoleCommand('Admin', [self::PERMISSION_ID]));
    }
}
