<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\AddContact\AddContactCommand;
use App\Client\Application\Command\AddContact\AddContactHandler;
use App\Client\Application\Command\DeactivateContact\DeactivateContactCommand;
use App\Client\Application\Command\DeactivateContact\DeactivateContactHandler;
use App\Client\Application\Command\LinkUserToContact\LinkUserToContactCommand;
use App\Client\Application\Command\LinkUserToContact\LinkUserToContactHandler;
use App\Client\Application\Command\ProvisionContactAccess\ProvisionContactAccessCommand;
use App\Client\Application\Command\ProvisionContactAccess\ProvisionContactAccessHandler;
use App\Client\Application\Command\ReactivateContact\ReactivateContactCommand;
use App\Client\Application\Command\ReactivateContact\ReactivateContactHandler;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactCommand;
use App\Client\Application\Command\SoftDeleteContact\SoftDeleteContactHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Application\Query\FindContactById\FindContactByIdHandler;
use App\Client\Application\Query\FindContactById\FindContactByIdQuery;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}')]
final class ContactController extends AbstractController
{
    #[IsGranted('contact.create')]
    #[Route('/create', name: 'contact_create', methods: ['POST'])]
    public function createContact(
        string $clientId,
        Request $request,
        AddContactHandler $addContact,
    ): Response {
        $firstName   = trim((string) $request->request->get('first_name', ''));
        $lastName    = trim((string) $request->request->get('last_name', ''));
        $email       = trim((string) $request->request->get('email', ''));
        $phoneNumber = trim((string) $request->request->get('phone_number', ''));
        $role        = trim((string) $request->request->get('role', ''));

        try {
            ($addContact)(new AddContactCommand(
                clientId:    $clientId,
                firstName:   $firstName,
                lastName:    $lastName,
                email:       $email,
                phoneNumber: $phoneNumber,
                role:        $role,
            ));

            $this->addFlash('success', 'Contacto agregado.');
        } catch (ClientNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('user.create')]
    #[Route('/{contactId}/create-user', name: 'contact_create_user', methods: ['POST'])]
    public function createContactUser(
        string $clientId,
        string $contactId,
        Request $request,
        FindContactByIdHandler $findContact,
        ProvisionContactAccessHandler $provision,
    ): Response {
        try {
            $contact = $findContact(new FindContactByIdQuery($contactId));
        } catch (ContactNotFoundException) {
            throw $this->createNotFoundException('Contacto no encontrado.');
        }

        if ($contact->clientId !== $clientId) {
            throw $this->createNotFoundException('Contacto no encontrado para este cliente.');
        }

        if ($contact->userId !== null) {
            $this->addFlash('warning', 'El contacto ya tiene un acceso al sistema vinculado.');

            return $this->redirectToRoute('client_show', ['id' => $clientId]);
        }

        $username        = trim((string) $request->request->get('username', ''));
        $password        = (string) $request->request->get('password', '');
        $passwordConfirm = (string) $request->request->get('password_confirm', '');
        $roleId          = trim((string) $request->request->get('role_id', ''));

        if ($password !== $passwordConfirm) {
            $this->addFlash('error', 'Las contraseñas no coinciden.');

            return $this->redirectToRoute('client_show', [
                'id'            => $clientId,
                'contactAccess' => $contactId,
                'contactMode'   => 'create',
            ]);
        }

        try {
            ($provision)(new ProvisionContactAccessCommand(
                contactId:   $contactId,
                clientId:    $clientId,
                username:    $username,
                rawPassword: $password,
                roleId:      $roleId,
            ));

            $this->addFlash('success', 'Acceso al sistema creado y vinculado al contacto.');

            return $this->redirectToRoute('client_show', ['id' => $clientId]);
        } catch (UsernameAlreadyExistsException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (RoleNotFoundException) {
            $this->addFlash('error', 'El rol seleccionado no existe.');
        } catch (\App\Shared\Exceptions\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', [
            'id'            => $clientId,
            'contactAccess' => $contactId,
            'contactMode'   => 'create',
        ]);
    }

    #[IsGranted('contact.update')]
    #[Route('/{contactId}/link-user', name: 'contact_link_user', methods: ['POST'])]
    public function linkContactUser(
        string $clientId,
        string $contactId,
        Request $request,
        LinkUserToContactHandler $linkUserToContact,
    ): Response {
        $userId = trim((string) $request->request->get('user_id', ''));

        try {
            ($linkUserToContact)(new LinkUserToContactCommand(
                contactId: $contactId,
                userId:    $userId,
            ));

            $this->addFlash('success', 'Usuario vinculado al contacto.');
        } catch (ContactNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('contact.update')]
    #[Route('/{contactId}/deactivate', name: 'contact_deactivate', methods: ['POST'])]
    public function deactivateContact(
        string $clientId,
        string $contactId,
        DeactivateContactHandler $deactivateContact,
    ): Response {
        try {
            ($deactivateContact)(new DeactivateContactCommand($contactId));
            $this->addFlash('success', 'Contacto desactivado.');
        } catch (ContactNotFoundException) {
            throw $this->createNotFoundException('Contacto no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('contact.update')]
    #[Route('/{contactId}/reactivate', name: 'contact_reactivate', methods: ['POST'])]
    public function reactivateContact(
        string $clientId,
        string $contactId,
        ReactivateContactHandler $reactivateContact,
    ): Response {
        try {
            ($reactivateContact)(new ReactivateContactCommand($contactId));
            $this->addFlash('success', 'Contacto reactivado.');
        } catch (ContactNotFoundException) {
            throw $this->createNotFoundException('Contacto no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('contact.delete')]
    #[Route('/{contactId}/delete', name: 'contact_delete', methods: ['POST'])]
    public function deleteContact(
        string $clientId,
        string $contactId,
        SoftDeleteContactHandler $deleteContact,
    ): Response {
        try {
            ($deleteContact)(new SoftDeleteContactCommand($contactId));
            $this->addFlash('success', 'Contacto eliminado lógicamente.');
        } catch (ContactNotFoundException) {
            throw $this->createNotFoundException('Contacto no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }
}
