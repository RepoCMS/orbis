<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanCommand;
use App\Inventory\Application\Command\ApproveTechnicianLoan\ApproveTechnicianLoanHandler;
use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanCommand;
use App\Inventory\Application\Command\CancelTechnicianLoan\CancelTechnicianLoanHandler;
use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanCommand;
use App\Inventory\Application\Command\CreateTechnicianLoan\CreateTechnicianLoanHandler;
use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanCommand;
use App\Inventory\Application\Command\RejectTechnicianLoan\RejectTechnicianLoanHandler;
use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansHandler;
use App\Inventory\Application\Query\ListTechnicianLoans\ListTechnicianLoansQuery;
use App\Inventory\Application\Query\Response\TechnicianLoanResponse;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/loans')]
final class TechnicianLoanController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_loan_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListTechnicianLoansHandler $handler,
    ): Response {
        $status = $request->query->get('status') ?: null;

        $query = new ListTechnicianLoansQuery(
            status:       $status,
            technicianId: null,
        );

        return $this->render('inventory/technician_loan/list.html.twig', [
            'loans'        => $handler($query),
            'statusFilter' => $status,
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_loan_show', methods: ['GET'])]
    public function show(
        string $id,
        TechnicianLoanRepositoryInterface $repository,
    ): Response {
        $loan = $repository->findById(new TechnicianLoanIdValueObject($id));

        if ($loan === null) {
            throw $this->createNotFoundException('Préstamo no encontrado.');
        }

        $response = TechnicianLoanResponse::fromEntity($loan);

        return $this->render('inventory/technician_loan/show.html.twig', [
            'loan' => $response,
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_loan_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('inventory/technician_loan/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_loan_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateTechnicianLoanHandler $handler,
    ): Response {
        $serializedStockId = trim((string) $request->request->get('serialized_stock_id', ''));
        $fromTechnicianId  = trim((string) $request->request->get('from_technician_id', ''));
        $toTechnicianId    = trim((string) $request->request->get('to_technician_id', ''));
        $ticketId          = trim((string) $request->request->get('ticket_id', '')) ?: null;
        $notes             = trim((string) $request->request->get('notes', '')) ?: null;

        try {
            ($handler)(new CreateTechnicianLoanCommand(
                serializedStockId: $serializedStockId,
                fromTechnicianId:  $fromTechnicianId,
                toTechnicianId:    $toTechnicianId,
                ticketId:          $ticketId,
                notes:             $notes,
            ));
            $this->addFlash('success', 'Préstamo creado.');

            return $this->redirectToRoute('inventory_loan_list');
        } catch (\InvalidArgumentException | \LogicException | DomainException $e) {
            return $this->render('inventory/technician_loan/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => compact('serializedStockId', 'fromTechnicianId', 'toTechnicianId', 'ticketId', 'notes'),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/approve', name: 'inventory_loan_approve', methods: ['POST'])]
    public function approve(
        string $id,
        ApproveTechnicianLoanHandler $handler,
    ): Response {
        $user   = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new ApproveTechnicianLoanCommand($id, $userId));
            $this->addFlash('success', 'Préstamo aprobado. Movimiento de stock registrado.');
        } catch (\InvalidArgumentException | \LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_loan_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/reject', name: 'inventory_loan_reject', methods: ['POST'])]
    public function reject(
        string $id,
        Request $request,
        RejectTechnicianLoanHandler $handler,
    ): Response {
        $reason = trim((string) $request->request->get('reason', ''));
        $user   = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new RejectTechnicianLoanCommand($id, $reason, $userId));
            $this->addFlash('success', 'Préstamo rechazado.');
        } catch (\InvalidArgumentException | \LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_loan_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'inventory_loan_cancel', methods: ['POST'])]
    public function cancel(
        string $id,
        CancelTechnicianLoanHandler $handler,
    ): Response {
        $user   = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CancelTechnicianLoanCommand($id, $userId));
            $this->addFlash('success', 'Préstamo cancelado.');
        } catch (\InvalidArgumentException | \LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_loan_list');
    }
}
