<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\ChangePermissionDescription\ChangePermissionDescriptionCommand;
use App\Identity\Application\Command\ChangePermissionDescription\ChangePermissionDescriptionHandler;
use App\Identity\Application\Command\CreateRole\CreateRoleCommand;
use App\Identity\Application\Command\CreateRole\CreateRoleHandler;
use App\Identity\Application\Command\DeleteRole\DeleteRoleCommand;
use App\Identity\Application\Command\DeleteRole\DeleteRoleHandler;
use App\Identity\Application\Command\RenameRole\RenameRoleCommand;
use App\Identity\Application\Command\RenameRole\RenameRoleHandler;
use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleCommand;
use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleHandler;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsCommand;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsHandler;
use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdHandler;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdQuery;
use App\Identity\Application\Query\ListPermissions\ListPermissionsHandler;
use App\Identity\Application\Query\ListPermissions\ListPermissionsQuery;
use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/identity')]
final class ApiIdentityRoleController extends AbstractController
{
    #[IsGranted('role.view')]
    #[Route('/roles', name: 'api_identity_roles_list', methods: ['GET'])]
    public function listRoles(ListRolesHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new ListRolesQuery())]);
    }

    #[IsGranted('role.create')]
    #[Route('/roles', name: 'api_identity_roles_create', methods: ['POST'])]
    public function createRole(Request $request, CreateRoleHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateRoleCommand(
                name: trim((string) ($payload['name'] ?? '')),
                slug: isset($payload['slug']) ? trim((string) $payload['slug']) : null,
                permissionIds: $payload['permissionIds'] ?? [],
            ));

            return $this->json(['data' => ['name' => $payload['name']]], Response::HTTP_CREATED);
        } catch (RoleNameAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('role.view')]
    #[Route('/roles/{id}', name: 'api_identity_roles_show', methods: ['GET'])]
    public function showRole(string $id, FindRoleByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindRoleByIdQuery($id))]);
        } catch (RoleNotFoundException) {
            return $this->json(['error' => 'Rol no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('role.update')]
    #[Route('/roles/{id}', name: 'api_identity_roles_update', methods: ['PATCH'])]
    public function updateRole(string $id, Request $request, RenameRoleHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new RenameRoleCommand(
                roleId:  $id,
                newName: trim((string) ($payload['name'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (RoleNotFoundException) {
            return $this->json(['error' => 'Rol no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (RoleNameAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('role.assign_permission')]
    #[Route('/roles/{id}/permissions', name: 'api_identity_roles_permissions', methods: ['POST'])]
    public function syncPermissions(string $id, Request $request, SyncRolePermissionsHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new SyncRolePermissionsCommand(
                roleId:        $id,
                permissionIds: $payload['permissionIds'] ?? [],
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (RoleNotFoundException) {
            return $this->json(['error' => 'Rol no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('role.delete')]
    #[Route('/roles/{id}', name: 'api_identity_roles_delete', methods: ['DELETE'])]
    public function deleteRole(string $id, DeleteRoleHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeleteRoleCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (RoleNotFoundException) {
            return $this->json(['error' => 'Rol no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('role.assign_permission')]
    #[Route('/roles/{roleId}/permissions/{permissionId}', name: 'api_identity_roles_revoke_permission', methods: ['DELETE'])]
    public function revokePermission(
        string $roleId,
        string $permissionId,
        RevokePermissionFromRoleHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new RevokePermissionFromRoleCommand($roleId, $permissionId));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (RoleNotFoundException) {
            return $this->json(['error' => 'Rol no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException | DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('permission.view')]
    #[Route('/permissions', name: 'api_identity_permissions_list', methods: ['GET'])]
    public function listPermissions(ListPermissionsHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new ListPermissionsQuery())]);
    }

    #[IsGranted('permission.update')]
    #[Route('/permissions/{id}', name: 'api_identity_permissions_update', methods: ['PATCH'])]
    public function updatePermission(
        string $id,
        Request $request,
        ChangePermissionDescriptionHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $description = trim((string) ($payload['description'] ?? ''));

        if ($description === '') {
            return $this->json(
                ['error' => 'La descripción del permiso es requerida.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            ($handler)(new ChangePermissionDescriptionCommand($id, $description));

            return $this->json(['data' => ['id' => $id]]);
        } catch (PermissionNotFoundException) {
            return $this->json(['error' => 'Permiso no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
