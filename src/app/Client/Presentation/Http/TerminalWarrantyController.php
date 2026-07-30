<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyCommand;
use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalHandler;
use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalQuery;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdHandler;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}/terminals/{terminalId}/warranties')]
final class TerminalWarrantyController extends AbstractController
{
    #[IsGranted('client.view')]
    #[Route('', name: 'client_terminal_warranty_list', methods: ['GET'])]
    public function listWarranties(
        string $clientId,
        string $terminalId,
        FindWarrantiesByTerminalHandler $listWarranties,
    ): Response {
        $warranties = ($listWarranties)(new FindWarrantiesByTerminalQuery($terminalId));

        return $this->render('client/warranty/list.html.twig', [
            'clientId'   => $clientId,
            'terminalId' => $terminalId,
            'warranties' => $warranties,
        ]);
    }

    #[IsGranted('client.update')]
    #[Route('/new', name: 'client_terminal_warranty_new', methods: ['GET'])]
    public function newWarrantyForm(
        string $clientId,
        string $terminalId,
    ): Response {
        return $this->render('client/warranty/form.html.twig', [
            'clientId'   => $clientId,
            'terminalId' => $terminalId,
            'errors'     => [],
            'input'      => [],
        ]);
    }

    #[IsGranted('client.update')]
    #[Route('', name: 'client_terminal_warranty_create', methods: ['POST'])]
    public function createWarranty(
        string $clientId,
        string $terminalId,
        Request $request,
        RegisterWarrantyHandler $registerWarranty,
    ): Response {
        $type      = trim((string) $request->request->get('type', ''));
        $startDate = trim((string) $request->request->get('start_date', ''));
        $endDate   = trim((string) $request->request->get('end_date', ''));
        $terms     = $request->request->has('terms') ? trim((string) $request->request->get('terms', '')) : null;

        try {
            ($registerWarranty)(new RegisterWarrantyCommand(
                terminalId: $terminalId,
                type:       $type,
                startDate:  $startDate,
                endDate:    $endDate,
                terms:      $terms,
            ));

            $this->addFlash('success', 'Garantía registrada exitosamente.');

            return $this->redirectToRoute('client_terminal_warranty_list', [
                'clientId'   => $clientId,
                'terminalId' => $terminalId,
            ]);
        } catch (TerminalNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('client/warranty/form.html.twig', [
            'clientId'   => $clientId,
            'terminalId' => $terminalId,
            'errors'     => [],
            'input'      => compact('type', 'startDate', 'endDate', 'terms'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('client.view')]
    #[Route('/{id}', name: 'client_terminal_warranty_show', methods: ['GET'])]
    public function showWarranty(
        string $clientId,
        string $terminalId,
        string $id,
        FindWarrantyByIdHandler $findWarranty,
    ): Response {
        try {
            $warranty = ($findWarranty)(new FindWarrantyByIdQuery($id));
        } catch (TerminalWarrantyNotFoundException) {
            throw $this->createNotFoundException('Garantía no encontrada.');
        }

        return $this->render('client/warranty/show.html.twig', [
            'clientId'   => $clientId,
            'terminalId' => $terminalId,
            'warranty'   => $warranty,
        ]);
    }
}
