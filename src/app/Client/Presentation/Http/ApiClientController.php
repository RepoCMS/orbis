<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateClient\ActivateClientCommand;
use App\Client\Application\Command\ActivateClient\ActivateClientHandler;
use App\Client\Application\Command\DeactivateClient\DeactivateClientCommand;
use App\Client\Application\Command\DeactivateClient\DeactivateClientHandler;
use App\Client\Application\Command\DeleteClient\DeleteClientCommand;
use App\Client\Application\Command\DeleteClient\DeleteClientHandler;
use App\Client\Application\Command\RegisterClient\RegisterClientCommand;
use App\Client\Application\Command\RegisterClient\RegisterClientHandler;
use App\Client\Application\Command\UpdateClient\UpdateClientCommand;
use App\Client\Application\Command\UpdateClient\UpdateClientHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\ClientRucAlreadyExistsException;
use App\Client\Application\Query\FindClientById\FindClientByIdHandler;
use App\Client\Application\Query\FindClientById\FindClientByIdQuery;
use App\Client\Application\Query\ListClients\ListClientsHandler;
use App\Client\Application\Query\ListClients\ListClientsQuery;
use App\Client\Domain\Enums\ClientStatus;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/clients')]
final class ApiClientController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'api_client_clients_list', methods: ['GET'])]
    public function list(Request $request, ListClientsHandler $handler): JsonResponse
    {
        $statusRaw = $request->query->get('status');
        $status = $statusRaw ? ClientStatus::tryFrom($statusRaw) : null;

        $clients = $handler(new ListClientsQuery(
            name: $request->query->get('q'),
            ruc: null,
            status: $status,
        ));

        return $this->json(['data' => $clients]);
    }

    #[IsGranted('client.create')]
    #[Route('', name: 'api_client_clients_create', methods: ['POST'])]
    public function create(Request $request, RegisterClientHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new RegisterClientCommand(
                name:    trim((string) ($payload['name'] ?? '')),
                ruc:     trim((string) ($payload['ruc'] ?? '')),
                address: isset($payload['address']) ? trim((string) $payload['address']) : null,
            ));

            return $this->json(['data' => ['name' => $payload['name']]], Response::HTTP_CREATED);
        } catch (ClientRucAlreadyExistsException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'api_client_clients_show', methods: ['GET'])]
    public function show(string $id, FindClientByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindClientByIdQuery($id))]);
        } catch (ClientNotFoundException) {
            return $this->json(['error' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}', name: 'api_client_clients_update', methods: ['PATCH'])]
    public function update(string $id, Request $request, UpdateClientHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateClientCommand(
                clientId: $id,
                name:     trim((string) ($payload['name'] ?? '')),
                address:  isset($payload['address']) ? trim((string) $payload['address']) : null,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ClientNotFoundException) {
            return $this->json(['error' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.manage')]
    #[Route('/{id}/deactivate', name: 'api_client_clients_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateClientHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeactivateClientCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'INACTIVE']]);
        } catch (ClientNotFoundException) {
            return $this->json(['error' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.manage')]
    #[Route('/{id}/reactivate', name: 'api_client_clients_reactivate', methods: ['POST'])]
    public function reactivate(string $id, ActivateClientHandler $handler): JsonResponse
    {
        try {
            ($handler)(new ActivateClientCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'ACTIVE']]);
        } catch (ClientNotFoundException) {
            return $this->json(['error' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.delete')]
    #[Route('/{id}', name: 'api_client_clients_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteClientHandler $handler): JsonResponse
    {
        try {
            ($handler)(new DeleteClientCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ClientNotFoundException) {
            return $this->json(['error' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
