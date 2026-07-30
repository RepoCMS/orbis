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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}/contracts')]
final class ContractController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'client_contract_list', methods: ['GET'])]
    public function listContracts(
        string $clientId,
        FindContractsByClientHandler $listContracts,
    ): Response {
        $contracts = ($listContracts)(new FindContractsByClientQuery($clientId));

        return $this->render('client/contract/list.html.twig', [
            'clientId'  => $clientId,
            'contracts' => $contracts,
        ]);
    }

    #[IsGranted('client.create')]
    #[Route('/new', name: 'client_contract_new', methods: ['GET'])]
    public function newContractForm(
        string $clientId,
    ): Response {
        return $this->render('client/contract/form.html.twig', [
            'clientId' => $clientId,
            'errors'   => [],
            'input'    => [],
        ]);
    }

    #[IsGranted('client.create')]
    #[Route('', name: 'client_contract_create', methods: ['POST'])]
    public function createContract(
        string $clientId,
        Request $request,
        CreateContractHandler $createContract,
    ): Response {
        $startDate = trim((string) $request->request->get('startDate', ''));
        $endDate   = trim((string) $request->request->get('endDate', '')) ?: null;
        $terms     = trim((string) $request->request->get('terms', '')) ?: null;
        $scope     = trim((string) $request->request->get('scope', '')) ?: null;

        try {
            ($createContract)(new CreateContractCommand(
                clientId:  $clientId,
                startDate: $startDate,
                endDate:   $endDate,
                terms:     $terms,
                scope:     $scope,
            ));

            $this->addFlash('success', 'Contrato creado exitosamente.');

            return $this->redirectToRoute('client_show', ['id' => $clientId]);
        } catch (ClientNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('client/contract/form.html.twig', [
            'clientId' => $clientId,
            'errors'   => [],
            'input'    => compact('startDate', 'endDate', 'terms', 'scope'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'client_contract_show', methods: ['GET'])]
    public function showContract(
        string $clientId,
        string $id,
        FindContractByIdHandler $findContract,
    ): Response {
        try {
            $contract = ($findContract)(new FindContractByIdQuery($id));
        } catch (ContractNotFoundException) {
            throw $this->createNotFoundException('Contrato no encontrado.');
        }

        if ($contract->clientId !== $clientId) {
            throw $this->createNotFoundException('Contrato no encontrado para este cliente.');
        }

        return $this->render('client/contract/show.html.twig', [
            'clientId' => $clientId,
            'contract' => $contract,
        ]);
    }

    #[IsGranted('client.update')]
    #[Route('/{id}/cancel', name: 'client_contract_cancel', methods: ['POST'])]
    public function cancelContract(
        string $clientId,
        string $id,
        CancelContractHandler $cancelContract,
    ): Response {
        try {
            ($cancelContract)(new CancelContractCommand($id));
            $this->addFlash('success', 'Contrato cancelado exitosamente.');
        } catch (ContractNotFoundException) {
            throw $this->createNotFoundException('Contrato no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }
}
