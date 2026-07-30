<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\CancelContract\CancelContractCommand;
use App\Client\Application\Command\CancelContract\CancelContractHandler;
use App\Client\Application\Command\CreateContract\CreateContractCommand;
use App\Client\Application\Command\CreateContract\CreateContractHandler;
use App\Client\Application\Command\UpdateContract\UpdateContractCommand;
use App\Client\Application\Command\UpdateContract\UpdateContractHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Application\Query\FindContractById\FindContractByIdHandler;
use App\Client\Application\Query\FindContractById\FindContractByIdQuery;
use App\Client\Application\Query\FindContractsByClient\FindContractsByClientHandler;
use App\Client\Application\Query\FindContractsByClient\FindContractsByClientQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/client/clients/{clientId}/contracts')]
final class ApiClientContractController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'api_client_contracts_list', methods: ['GET'])]
    public function list(string $clientId, FindContractsByClientHandler $handler): JsonResponse
    {
        return $this->json(['data' => $handler(new FindContractsByClientQuery($clientId))]);
    }

    #[IsGranted('client.update')]
    #[Route('', name: 'api_client_contracts_create', methods: ['POST'])]
    public function create(string $clientId, Request $request, CreateContractHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new CreateContractCommand(
                clientId:  $clientId,
                startDate: trim((string) ($payload['startDate'] ?? '')),
                endDate:   isset($payload['endDate']) ? trim((string) $payload['endDate']) : null,
                terms:     isset($payload['terms']) ? trim((string) $payload['terms']) : null,
                scope:     isset($payload['scope']) ? trim((string) $payload['scope']) : null,
            ));

            return $this->json(['data' => ['clientId' => $clientId]], Response::HTTP_CREATED);
        } catch (ClientNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'api_client_contracts_show', methods: ['GET'])]
    public function show(string $clientId, string $id, FindContractByIdHandler $handler): JsonResponse
    {
        try {
            return $this->json(['data' => $handler(new FindContractByIdQuery($id))]);
        } catch (ContractNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}', name: 'api_client_contracts_update', methods: ['PATCH'])]
    public function update(string $clientId, string $id, Request $request, UpdateContractHandler $handler): JsonResponse
    {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateContractCommand(
                contractId: $id,
                terms:     isset($payload['terms']) ? trim((string) $payload['terms']) : null,
                scope:     isset($payload['scope']) ? trim((string) $payload['scope']) : null,
                endDate:   isset($payload['endDate']) ? trim((string) $payload['endDate']) : null,
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ContractNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/cancel', name: 'api_client_contracts_cancel', methods: ['POST'])]
    public function cancel(string $clientId, string $id, CancelContractHandler $handler): JsonResponse
    {
        try {
            ($handler)(new CancelContractCommand($id));

            return $this->json(['data' => ['id' => $id]]);
        } catch (ContractNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
