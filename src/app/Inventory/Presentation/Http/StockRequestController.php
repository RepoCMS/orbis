<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\ApproveRequest\ApproveRequestCommand;
use App\Inventory\Application\Command\ApproveRequest\ApproveRequestHandler;
use App\Inventory\Application\Command\CancelRequest\CancelRequestCommand;
use App\Inventory\Application\Command\CancelRequest\CancelRequestHandler;
use App\Inventory\Application\Command\FulfillRequest\FulfillRequestCommand;
use App\Inventory\Application\Command\FulfillRequest\FulfillRequestHandler;
use App\Inventory\Application\Command\RejectRequest\RejectRequestCommand;
use App\Inventory\Application\Command\RejectRequest\RejectRequestHandler;
use App\Inventory\Application\Command\SubmitRequest\SubmitRequestCommand;
use App\Inventory\Application\Command\SubmitRequest\SubmitRequestHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Application\Query\FindRequestById\FindRequestByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListProducts\ListProductsHandler;
use App\Inventory\Application\Query\ListRequests\ListRequestsHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/requests')]
final class StockRequestController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_request_list', methods: ['GET'])]
    public function list(Request $request, ListRequestsHandler $handler): Response
    {
        $status = $request->query->get('status') ?: null;

        return $this->render('inventory/request/list.html.twig', [
            'requests'     => $handler($status),
            'statusFilter' => $status,
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_request_new', methods: ['GET'])]
    public function newForm(ListProductsHandler $listProducts, ListLocationsHandler $listLocations): Response
    {
        return $this->render('inventory/request/form.html.twig', [
            'errors'    => [],
            'input'     => [],
            'products'  => $listProducts(),
            'locations' => $listLocations(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_request_create', methods: ['POST'])]
    public function create(
        Request $request,
        SubmitRequestHandler $handler,
        ListProductsHandler $listProducts,
        ListLocationsHandler $listLocations,
    ): Response {
        $productId            = trim((string) $request->request->get('product_id', ''));
        $serializedStockId    = trim((string) $request->request->get('serialized_stock_id', '')) ?: null;
        $quantity             = (int) $request->request->get('quantity', 1);
        $sourceLocationId     = trim((string) $request->request->get('source_location_id', ''));
        $destinationLocationId = trim((string) $request->request->get('destination_location_id', ''));
        $notes                = trim((string) $request->request->get('notes', '')) ?: null;
        $userId               = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new SubmitRequestCommand(
                $productId,
                $serializedStockId,
                $quantity,
                $sourceLocationId,
                $destinationLocationId,
                $userId,
                $notes,
            ));
            $this->addFlash('success', 'Solicitud de stock enviada.');

            return $this->redirectToRoute('inventory_request_list');
        } catch (DomainException $e) {
            return $this->render('inventory/request/form.html.twig', [
                'errors'    => [$e->getMessage()],
                'input'     => compact('productId', 'serializedStockId', 'quantity', 'sourceLocationId', 'destinationLocationId', 'notes'),
                'products'  => $listProducts(),
                'locations' => $listLocations(),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_request_show', methods: ['GET'])]
    public function show(string $id, FindRequestByIdHandler $findRequest): Response
    {
        try {
            $stockRequest = $findRequest($id);
        } catch (RequestNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/request/show.html.twig', [
            'stockRequest' => $stockRequest,
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/approve', name: 'inventory_request_approve', methods: ['POST'])]
    public function approve(string $id, ApproveRequestHandler $handler): Response
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new ApproveRequestCommand($id, $userId));
            $this->addFlash('success', 'Solicitud aprobada.');
        } catch (RequestNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_request_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/reject', name: 'inventory_request_reject', methods: ['POST'])]
    public function reject(string $id, Request $request, RejectRequestHandler $handler): Response
    {
        $reason = trim((string) $request->request->get('reason', ''));
        $userId = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new RejectRequestCommand($id, $userId, $reason));
            $this->addFlash('success', 'Solicitud rechazada.');
        } catch (RequestNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_request_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/cancel', name: 'inventory_request_cancel', methods: ['POST'])]
    public function cancel(string $id, CancelRequestHandler $handler): Response
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new CancelRequestCommand($id, $userId));
            $this->addFlash('success', 'Solicitud cancelada.');
        } catch (RequestNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_request_list');
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/fulfill', name: 'inventory_request_fulfill', methods: ['POST'])]
    public function fulfill(string $id, FulfillRequestHandler $handler): Response
    {
        try {
            ($handler)(new FulfillRequestCommand($id));
            $this->addFlash('success', 'Solicitud ejecutada. El stock fue transferido.');
        } catch (RequestNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_request_show', ['id' => $id]);
    }
}
