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
use App\Client\Application\Command\UnlinkUserFromContact\UnlinkUserFromContactCommand;
use App\Client\Application\Command\UnlinkUserFromContact\UnlinkUserFromContactHandler;
use App\Client\Application\Command\UpdateContact\UpdateContactCommand;
use App\Client\Application\Command\UpdateContact\UpdateContactHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Application\Query\FindContactById\FindContactByIdHandler;
use App\Client\Application\Query\FindContactById\FindContactByIdQuery;
use App\Client\Application\Query\FindContactsByClient\FindContactsByClientHandler;
use App\Client\Application\Query\FindContactsByClient\FindContactsByClientQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/clients/{clientId}/contacts')]
final class ApiClientContactController extends AbstractController
{
    #[IsGranted('contact.view')]
    #[Route('', name: 'api_client_contacts_list', methods: ['GET'])]
    public function list(string $clientId, FindContactsByClientHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new FindContactsByClientQuery($clientId))]);
    }

    #[IsGranted('contact.create')]
    #[Route('', name: 'api_client_contacts_create', methods: ['POST'])]
    public function create(string $clientId, Request $request, AddContactHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            $contactId = ($handler)(new AddContactCommand(
                clientId:    $clientId,
                firstName:   trim((string) ($payload['firstName'] ?? '')),
                lastName:    trim((string) ($payload['lastName'] ?? '')),
                email:       trim((string) ($payload['email'] ?? '')),
                phoneNumber: trim((string) ($payload['phoneNumber'] ?? '')),
                role:        trim((string) ($payload['role'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $contactId, 'hasUser' => false]], Response::HTTP_CREATED);
        } catch (ClientNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.view')]
    #[Route('/{id}', name: 'api_client_contacts_show', methods: ['GET'])]
    public function show(string $clientId, string $id, FindContactByIdHandler $handler): JsonResponse
    {
        try {
            $contact = $handler(new FindContactByIdQuery($id));

            if ($contact->clientId !== $clientId) {
                return $this->json(['error' => 'Contacto no encontrado para este cliente.'], Response::HTTP_NOT_FOUND);
            }

            return $this->json(['data' => $contact]);
        } catch (ContactNotFoundException) {
            return $this->json(['error' => 'Contacto no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}/link-user', name: 'api_client_contacts_link_user', methods: ['POST'])]
    public function linkUser(string $clientId, string $id, Request $request, LinkUserToContactHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new LinkUserToContactCommand(
                contactId: $id,
                userId:    trim((string) ($payload['userId'] ?? '')),
            ));

            return $this->json(['data' => ['contactId' => $id]]);
        } catch (ContactNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}', name: 'api_client_contacts_update', methods: ['PATCH'])]
    public function update(string $clientId, string $id, Request $request, UpdateContactHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateContactCommand(
                contactId:   $id,
                firstName:   trim((string) ($payload['firstName'] ?? '')),
                lastName:    trim((string) ($payload['lastName'] ?? '')),
                email:       trim((string) ($payload['email'] ?? '')),
                phoneNumber: trim((string) ($payload['phoneNumber'] ?? '')),
                role:        trim((string) ($payload['role'] ?? '')),
            ));

            return $this->json(['data' => ['contactId' => $id]]);
        } catch (ContactNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}/provision-access', name: 'api_client_contacts_provision_access', methods: ['POST'])]
    public function provisionContactAccess(
        string $clientId,
        string $id,
        Request $request,
        ProvisionContactAccessHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new ProvisionContactAccessCommand(
                contactId:  $id,
                clientId:   $clientId,
                username:   trim((string) ($payload['username'] ?? '')),
                rawPassword: (string) ($payload['password'] ?? ''),
                roleId:     trim((string) ($payload['roleId'] ?? '')),
            ));

            return $this->json(['data' => ['contactId' => $id, 'hasUser' => true]]);
        } catch (ContactNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}/unlink-user', name: 'api_client_contacts_unlink_user', methods: ['POST'])]
    public function unlinkUser(string $clientId, string $id, UnlinkUserFromContactHandler $handler): JsonResponse
    {
        try {
            ($handler)(new UnlinkUserFromContactCommand($id));

            return $this->json(['data' => ['contactId' => $id]]);
        } catch (ContactNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}/deactivate', name: 'api_client_contacts_deactivate', methods: ['POST'])]
    public function deactivate(string $clientId, string $id, DeactivateContactHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeactivateContactCommand($id));

            return $this->json(['data' => ['contactId' => $id, 'status' => 'INACTIVE']]);
        } catch (ContactNotFoundException) {
            return $this->json(['error' => 'Contacto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.update')]
    #[Route('/{id}/reactivate', name: 'api_client_contacts_reactivate', methods: ['POST'])]
    public function reactivate(string $clientId, string $id, ReactivateContactHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ReactivateContactCommand($id));

            return $this->json(['data' => ['contactId' => $id, 'status' => 'ACTIVE']]);
        } catch (ContactNotFoundException) {
            return $this->json(['error' => 'Contacto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('contact.delete')]
    #[Route('/{id}', name: 'api_client_contacts_delete', methods: ['DELETE'])]
    public function delete(string $clientId, string $id, SoftDeleteContactHandler $handler): JsonResponse
    {
        try {
            ($handler)(new SoftDeleteContactCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ContactNotFoundException) {
            return $this->json(['error' => 'Contacto no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
