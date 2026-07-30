<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\ActivateUser\ActivateUserCommand;
use App\Identity\Application\Command\ActivateUser\ActivateUserHandler;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserHandler;
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
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenCommand;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Command\ChangeUsername\ChangeUsernameCommand;
use App\Identity\Application\Command\ChangeUsername\ChangeUsernameHandler;
use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\ChangePassword\ChangePasswordHandler;
use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserCommand;
use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserHandler;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\FindUserById\FindUserByIdHandler;
use App\Identity\Application\Query\FindUserById\FindUserByIdQuery;
use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Identity\Application\Query\ListUsers\ListUsersHandler;
use App\Identity\Application\Query\ListUsers\ListUsersQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
final class UserController extends AbstractController
{
    #[IsGranted('user.view_all')]
    #[Route('', name: 'user_list', methods: ['GET'])]
    public function list(ListUsersHandler $listUsers, ListRolesHandler $listRoles): Response
    {
        $users = $listUsers(new ListUsersQuery());
        $roles = $listRoles(new ListRolesQuery());

        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[$role->id] = $role->name;
        }

        return $this->render('user/list.html.twig', [
            'users'    => $users,
            'rolesById' => $rolesById,
        ]);
    }

    #[IsGranted('user.create')]
    #[Route('/new', name: 'user_new', methods: ['GET'])]
    public function newForm(ListRolesHandler $listRoles): Response
    {
        $roles = $listRoles(new ListRolesQuery());

        return $this->render('user/form.html.twig', [
            'roles'  => $roles,
            'input'  => [],
        ]);
    }

    #[IsGranted('user.create')]
    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, RegisterUserHandler $registerUser, ListRolesHandler $listRoles): Response
    {
        $username        = trim((string) $request->request->get('username', ''));
        $password        = (string) $request->request->get('password', '');
        $passwordConfirm = (string) $request->request->get('password_confirm', '');
        $roleId          = trim((string) $request->request->get('role_id', ''));

        if ($password !== $passwordConfirm) {
            $roles = $listRoles(new ListRolesQuery());

            return $this->render('user/form.html.twig', [
                'roles'  => $roles,
                'input'  => $request->request->all(),
                'errors' => ['Las contraseñas no coinciden.'],
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        try {
            ($registerUser)(new RegisterUserCommand(
                username:    $username,
                rawPassword: $password,
                roleId:      $roleId,
            ));

            $this->addFlash('success', 'Usuario creado correctamente.');

            return $this->redirectToRoute('user_list');
        } catch (UsernameAlreadyExistsException $e) {
            $errors = [$e->getMessage()];
        } catch (RoleNotFoundException $e) {
            $errors = [$e->getMessage()];
        } catch (DomainException $e) {
            $errors = [$e->getMessage()];
        }

        $roles = $listRoles(new ListRolesQuery());

        return $this->render('user/form.html.twig', [
            'roles'  => $roles,
            'input'  => $request->request->all(),
            'errors' => $errors ?? [],
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('user.view')]
    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(string $id, FindUserByIdHandler $findUser, ListRolesHandler $listRoles): Response
    {
        try {
            $user  = $findUser(new FindUserByIdQuery($id));
            $roles = $listRoles(new ListRolesQuery());

            $rolesById = [];
            foreach ($roles as $role) {
                $rolesById[$role->id] = $role->name;
            }

            return $this->render('user/show.html.twig', [
                'user'     => $user,
                'roleName' => $rolesById[$user->roleId] ?? $user->roleId,
                'roles'    => $roles,
            ]);
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }
    }

    #[IsGranted('user.edit')]
    #[Route('/{id}/edit', name: 'user_edit', methods: ['POST'])]
    public function edit(
        string $id,
        Request $request,
        ChangeUsernameHandler $changeUsername,
        ChangePasswordHandler $changePassword,
        AssignRoleToUserHandler $assignRole,
        FindUserByIdHandler $findUser,
        ListRolesHandler $listRoles,
    ): Response {
        $newUsername     = trim((string) $request->request->get('username', ''));
        $newPassword     = (string) $request->request->get('password', '');
        $passwordConfirm = (string) $request->request->get('password_confirm', '');
        $roleId          = trim((string) $request->request->get('role_id', ''));

        $errors = [];

        if ($newPassword !== '' && $newPassword !== $passwordConfirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        try {
            $user = $findUser(new FindUserByIdQuery($id));

            if ($newUsername !== '' && $newUsername !== $user->username) {
                ($changeUsername)(new ChangeUsernameCommand($id, $newUsername));
            }

            if ($newPassword !== '') {
                ($changePassword)(new ChangePasswordCommand($id, $newPassword));
            }

            if ($roleId !== '' && $roleId !== $user->roleId) {
                ($assignRole)(new AssignRoleToUserCommand($id, $roleId));
            }

            $this->addFlash('success', 'Datos del usuario actualizados.');
            return $this->redirectToRoute('user_show', ['id' => $id]);
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        $user  = $findUser(new FindUserByIdQuery($id));
        $roles = $listRoles(new ListRolesQuery());
        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[$role->id] = $role->name;
        }

        return $this->render('user/show.html.twig', [
            'user'     => $user,
            'roleName' => $rolesById[$user->roleId] ?? $user->roleId,
            'roles'    => $roles,
            'errors'   => $errors,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('user.verify')]
    #[Route('/{id}/verify', name: 'user_verify', methods: ['POST'])]
    public function verify(string $id, VerifyUserHandler $verify): Response
    {
        try {
            ($verify)(new VerifyUserCommand($id));
            $this->addFlash('success', 'Cuenta verificada y activada correctamente.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.edit')]
    #[Route('/{id}/resend-verification', name: 'user_resend_verification', methods: ['POST'])]
    public function resendVerification(
        string $id,
        GenerateActivationTokenHandler $generateToken,
    ): Response {
        try {
            ($generateToken)(new GenerateActivationTokenCommand(
                userId: $id,
            ));
            $this->addFlash('success', 'Correo de verificación enviado correctamente.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.deactivate')]
    #[Route('/{id}/deactivate', name: 'user_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateUserHandler $deactivateUser): Response
    {
        try {
            ($deactivateUser)(new DeactivateUserCommand($id));
            $this->addFlash('success', 'Usuario desactivado.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.activate')]
    #[Route('/{id}/reactivate', name: 'user_reactivate', methods: ['POST'])]
    public function reactivate(string $id, ReactivateUserHandler $reactivateUser): Response
    {
        try {
            ($reactivateUser)(new ReactivateUserCommand($id));
            $this->addFlash('success', 'Usuario reactivado.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.activate')]
    #[Route('/{id}/activate', name: 'user_activate', methods: ['POST'])]
    public function activate(string $id, ActivateUserHandler $activateUser): Response
    {
        try {
            ($activateUser)(new ActivateUserCommand($id));
            $this->addFlash('success', 'Usuario activado.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.unlock')]
    #[Route('/{id}/unlock', name: 'user_unlock', methods: ['POST'])]
    public function unlock(string $id, UnlockUserHandler $unlockUser): Response
    {
        try {
            ($unlockUser)(new UnlockUserCommand($id));
            $this->addFlash('success', 'Usuario desbloqueado.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('user_show', ['id' => $id]);
    }

    #[IsGranted('user.delete')]
    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(string $id, SoftDeleteUserHandler $deleteUser): Response
    {
        try {
            ($deleteUser)(new SoftDeleteUserCommand($id));
            $this->addFlash('success', 'Usuario eliminado lógicamente.');
        } catch (\App\Identity\Application\Exception\UserNotFoundException) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('user_show', ['id' => $id]);
        }

        return $this->redirectToRoute('user_list');
    }
}
