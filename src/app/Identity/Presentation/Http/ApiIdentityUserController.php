<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserCommand;
use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserHandler;
use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\ChangePassword\ChangePasswordHandler;
use App\Identity\Application\Command\ChangeUsername\ChangeUsernameCommand;
use App\Identity\Application\Command\ChangeUsername\ChangeUsernameHandler;
use App\Identity\Application\Command\ActivateUser\ActivateUserCommand;
use App\Identity\Application\Command\ActivateUser\ActivateUserHandler;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserHandler;
use App\Identity\Application\Command\DisableUserMfa\DisableUserMfaCommand;
use App\Identity\Application\Command\DisableUserMfa\DisableUserMfaHandler;
use App\Identity\Application\Command\InviteContactToPortal\InviteContactToPortalCommand;
use App\Identity\Application\Command\InviteContactToPortal\InviteContactToPortalHandler;
use App\Identity\Application\Command\ReactivateUser\ReactivateUserCommand;
use App\Identity\Application\Command\ReactivateUser\ReactivateUserHandler;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Command\SoftDeleteUser\SoftDeleteUserCommand;
use App\Identity\Application\Command\SoftDeleteUser\SoftDeleteUserHandler;
use App\Identity\Application\Command\UnlockUser\UnlockUserCommand;
use App\Identity\Application\Command\UnlockUser\UnlockUserHandler;
use App\Identity\Application\Command\VerifyUser\VerifyUserCommand;
use App\Identity\Application\Command\VerifyUser\VerifyUserHandler;
use App\Identity\Application\Exception\ContactAlreadyHasAccessException;
use App\Identity\Application\Exception\ContactNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Application\Query\FindUserById\FindUserByIdHandler;
use App\Identity\Application\Query\FindUserById\FindUserByIdQuery;
use App\Identity\Application\Query\ListUsers\ListUsersHandler;
use App\Identity\Application\Query\ListUsers\ListUsersQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/identity/users')]
final class ApiIdentityUserController extends AbstractController
{
    #[IsGranted('user.view')]
    #[Route('', name: 'api_identity_users_list', methods: ['GET'])]
    public function list(Request $request, ListUsersHandler $handler): JsonResponse
    {
        $page  = (int) ($request->query->get('page', '1'));
        $limit = (int) ($request->query->get('limit', '20'));

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $result = $handler(new ListUsersQuery($page, $limit));

        $items = $result->items;

        $status = $request->query->get('status');
        $role   = $request->query->get('role');
        $search = $request->query->get('q');

        if ($status !== null) {
            $items = array_values(array_filter(
                $items,
                static fn($u) => $u->status === $status,
            ));
        }

        if ($role !== null) {
            $items = array_values(array_filter(
                $items,
                static fn($u) => $u->roleId === $role,
            ));
        }

        if ($search !== null) {
            $q = mb_strtolower($search);
            $items = array_values(array_filter(
                $items,
                static fn($u) => str_contains(mb_strtolower($u->username), $q),
            ));
        }

        return $this->json([
            'data'       => $items,
            'pagination' => [
                'page'  => $result->page,
                'limit' => $result->limit,
                'total' => $result->total,
            ],
        ]);
    }

    #[IsGranted('user.create')]
    #[Route('', name: 'api_identity_users_create', methods: ['POST'])]
    public function create(Request $request, RegisterUserHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $userId = ($handler)(new RegisterUserCommand(
                username:   trim((string) ($payload['username'] ?? '')),
                rawPassword: (string) ($payload['password'] ?? ''),
                roleId:     trim((string) ($payload['roleId'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $userId]], Response::HTTP_CREATED);
        } catch (UsernameAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (RoleNotFoundException $e) {
            return $this->json(['error' => 'El rol especificado no existe.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.view')]
    #[Route('/{id}', name: 'api_identity_users_show', methods: ['GET'])]
    public function show(string $id, FindUserByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindUserByIdQuery($id))]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('user.update')]
    #[Route('/{id}', name: 'api_identity_users_update', methods: ['PATCH'])]
    public function update(
        string $id,
        Request $request,
        FindUserByIdHandler $findUser,
        ChangeUsernameHandler $changeUsername,
        AssignRoleToUserHandler $assignRole,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $user = $findUser(new FindUserByIdQuery($id));

            if (isset($payload['username']) && trim((string) $payload['username']) !== $user->username) {
                ($changeUsername)(new ChangeUsernameCommand($id, trim((string) $payload['username'])));
            }

            if (isset($payload['roleId']) && trim((string) $payload['roleId']) !== $user->roleId) {
                ($assignRole)(new AssignRoleToUserCommand($id, trim((string) $payload['roleId'])));
            }

            return $this->json(['data' => ['id' => $id]]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (UsernameAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.update')]
    #[Route('/{id}/change-password', name: 'api_identity_users_change_password', methods: ['POST'])]
    public function changePassword(
        string $id,
        Request $request,
        ChangePasswordHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new ChangePasswordCommand(
                userId:     $id,
                rawPassword: (string) ($payload['newPassword'] ?? ''),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.manage')]
    #[Route('/{id}/activate', name: 'api_identity_users_activate', methods: ['POST'])]
    public function activate(string $id, VerifyUserHandler $handler): JsonResponse
    {
        try {
            ($handler)(new VerifyUserCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'ACTIVE']]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.manage')]
    #[Route('/{id}/activate-user', name: 'api_identity_users_activate_user', methods: ['POST'])]
    public function activateUser(string $id, ActivateUserHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ActivateUserCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'ACTIVE']]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.manage')]
    #[Route('/{id}/deactivate', name: 'api_identity_users_deactivate', methods: ['POST'])]
    public function deactivate(string $id, Request $request, DeactivateUserHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $reason  = trim((string) ($payload['reason'] ?? ''));

        try {
            ($handler)(new DeactivateUserCommand($id, $reason));

            return $this->json(['data' => ['id' => $id, 'status' => 'INACTIVE']]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.unlock')]
    #[Route('/{id}/unlock', name: 'api_identity_users_unlock', methods: ['POST'])]
    public function unlock(string $id, UnlockUserHandler $handler): JsonResponse
    {
        try {
            ($handler)(new UnlockUserCommand($id));

            return $this->json(['data' => ['id' => $id, 'isLocked' => false]]);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.invite')]
    #[Route('/invite-contact', name: 'api_identity_users_invite_contact', methods: ['POST'])]
    public function inviteContact(Request $request, InviteContactToPortalHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $contactId = trim((string) ($payload['contactId'] ?? ''));

        if ($contactId === '') {
            return $this->json(
                ['error' => 'El ID del contacto es requerido.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $userId = ($handler)(new InviteContactToPortalCommand($contactId));

            return $this->json(['data' => ['id' => $userId]], Response::HTTP_CREATED);
        } catch (ContactNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ContactAlreadyHasAccessException|RoleNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (UsernameAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\App\Shared\Exceptions\DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.manage')]
    #[Route('/{id}/mfa/disable', name: 'api_identity_users_mfa_disable', methods: ['POST'])]
    public function disableMfa(string $id, DisableUserMfaHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DisableUserMfaCommand($id));

            return $this->json(['data' => ['id' => $id, 'mfaEnabled' => false]]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.manage')]
    #[Route('/{id}/revoke-mfa', name: 'api_identity_users_revoke_mfa', methods: ['POST'])]
    public function revokeMfa(string $id, RevokeMfaHandler $handler): JsonResponse
    {
        try {
            ($handler)(new RevokeMfaCommand($id));

            return $this->json(['data' => ['id' => $id, 'mfaEnabled' => false]]);
        } catch (\App\Shared\Exceptions\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('user.delete')]
    #[Route('/{id}', name: 'api_identity_users_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request, SoftDeleteUserHandler $handler): JsonResponse
    {
        $payload = $request->toArray();
        $reason  = trim((string) ($payload['reason'] ?? ''));

        try {
            ($handler)(new SoftDeleteUserCommand($id, $reason));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
