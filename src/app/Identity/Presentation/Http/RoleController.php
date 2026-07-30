<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\ActivateRole\ActivateRoleCommand;
use App\Identity\Application\Command\ActivateRole\ActivateRoleHandler;
use App\Identity\Application\Command\CreateRole\CreateRoleCommand;
use App\Identity\Application\Command\CreateRole\CreateRoleHandler;
use App\Identity\Application\Command\DeactivateRole\DeactivateRoleCommand;
use App\Identity\Application\Command\DeactivateRole\DeactivateRoleHandler;
use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleCommand;
use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleHandler;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsCommand;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsHandler;
use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdHandler;
use App\Identity\Application\Query\FindRoleById\FindRoleByIdQuery;
use App\Identity\Application\Query\ListPermissions\ListPermissionsHandler;
use App\Identity\Application\Query\ListPermissions\ListPermissionsQuery;
use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Identity\Domain\Exceptions\RoleIdException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/roles')]
final class RoleController extends AbstractController
{
    #[IsGranted('role.view_all')]
    #[Route('', name: 'role_list', methods: ['GET'])]
    public function list(ListRolesHandler $listRoles): Response
    {
        $roles = $listRoles(new ListRolesQuery());

        return $this->render('role/list.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[IsGranted('role.create')]
    #[Route('/new', name: 'role_new', methods: ['GET'])]
    public function newForm(ListPermissionsHandler $listPermissions): Response
    {
        $permissions = $listPermissions(new ListPermissionsQuery());

        return $this->render('role/form.html.twig', [
            'permissions' => $permissions,
            'errors'      => [],
            'input'       => [],
        ]);
    }

    #[IsGranted('role.create')]
    #[Route('', name: 'role_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateRoleHandler $createRole,
        ListPermissionsHandler $listPermissions,
    ): Response {
        $name          = trim((string) $request->request->get('name', ''));
        $permissionIds = (array) $request->request->all('permission_ids');

        $errors = [];

        try {
            ($createRole)(new CreateRoleCommand(
                name:          $name,
                permissionIds: $permissionIds,
            ));

            $this->addFlash('success', sprintf('Rol "%s" creado exitosamente.', $name));

            return $this->redirectToRoute('role_list');
        } catch (RoleNameAlreadyExistsException $e) {
            $errors[] = $e->getMessage();
        } catch (\InvalidArgumentException|DomainException $e) {
            $errors[] = $e->getMessage();
        }

        return $this->render('role/form.html.twig', [
            'permissions' => $listPermissions(new ListPermissionsQuery()),
            'errors'      => $errors,
            'input'       => ['name' => $name, 'permissionIds' => $permissionIds],
        ], new Response(null, 422));
    }

    #[IsGranted('role.view')]
    #[Route('/{id}', name: 'role_show', methods: ['GET'])]
    public function show(
        string $id,
        FindRoleByIdHandler $findRole,
        ListPermissionsHandler $listPermissions,
    ): Response {
        try {
            $role = $findRole(new FindRoleByIdQuery($id));
        } catch (RoleNotFoundException | RoleIdException) {
            throw $this->createNotFoundException('Rol no encontrado.');
        }

        $permissions = $listPermissions(new ListPermissionsQuery());

        return $this->render('role/show.html.twig', [
            'role'        => $role,
            'permissions' => $permissions,
        ]);
    }

    #[IsGranted('role.assign_permission')]
    #[Route('/{id}/permissions', name: 'role_sync_permissions', methods: ['POST'])]
    public function syncPermissions(
        string $id,
        Request $request,
        SyncRolePermissionsHandler $syncPermissions,
    ): Response {
        $permissionIds = (array) $request->request->all('permission_ids');

        try {
            ($syncPermissions)(new SyncRolePermissionsCommand(
                roleId:        $id,
                permissionIds: $permissionIds,
            ));
            $this->addFlash('success', 'Permisos actualizados correctamente.');
        } catch (RoleNotFoundException) {
            throw $this->createNotFoundException('Rol no encontrado.');
        } catch (\InvalidArgumentException | DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('role_show', ['id' => $id]);
    }

    #[Route('/{id}/deactivate', name: 'role_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateRoleHandler $deactivateRole): Response
    {
        try {
            ($deactivateRole)(new DeactivateRoleCommand($id));
            $this->addFlash('success', 'Rol desactivado.');
        } catch (RoleNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('role_show', ['id' => $id]);
    }

    #[IsGranted('role.assign_permission')]
    #[Route('/{roleId}/permissions/{permissionId}/revoke', name: 'role_revoke_permission', methods: ['POST'])]
    public function revokePermission(
        string $roleId,
        string $permissionId,
        RevokePermissionFromRoleHandler $revokePermission,
    ): Response {
        try {
            ($revokePermission)(new RevokePermissionFromRoleCommand($roleId, $permissionId));
            $this->addFlash('success', 'Permiso revocado correctamente.');
        } catch (RoleNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\InvalidArgumentException | DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('role_show', ['id' => $roleId]);
    }

    #[IsGranted('role.activate')]
    #[Route('/{id}/activate', name: 'role_activate', methods: ['POST'])]
    public function activate(string $id, ActivateRoleHandler $activateRole): Response
    {
        try {
            ($activateRole)(new ActivateRoleCommand($id));
            $this->addFlash('success', 'Rol activado.');
        } catch (RoleNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('role_show', ['id' => $id]);
    }
}
