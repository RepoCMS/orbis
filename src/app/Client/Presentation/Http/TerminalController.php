<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateTerminal\ActivateTerminalCommand;
use App\Client\Application\Command\ActivateTerminal\ActivateTerminalHandler;
use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalCommand;
use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalHandler;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalCommand;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalHandler;
use App\Client\Application\Command\SoftDeleteTerminal\SoftDeleteTerminalCommand;
use App\Client\Application\Command\SoftDeleteTerminal\SoftDeleteTerminalHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\SerialForTerminalAlreadyExistsException;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdQuery;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}/terminals')]
final class TerminalController extends AbstractController
{
    #[IsGranted('terminal.view')]
    #[Route('', name: 'client_terminal_list', methods: ['GET'])]
    public function listTerminals(
        string $clientId,
        FindTerminalsByClientHandler $listTerminals,
    ): Response {
        $terminals = ($listTerminals)(new FindTerminalsByClientQuery($clientId));

        return $this->render('client/terminal/list.html.twig', [
            'clientId'  => $clientId,
            'terminals' => $terminals,
        ]);
    }

    #[IsGranted('terminal.create')]
    #[Route('/new', name: 'client_terminal_new', methods: ['GET'])]
    public function newTerminalForm(
        string $clientId,
    ): Response {
        return $this->render('client/terminal/form.html.twig', [
            'clientId' => $clientId,
            'errors'   => [],
            'input'    => [],
        ]);
    }

    #[IsGranted('terminal.create')]
    #[Route('', name: 'client_terminal_create', methods: ['POST'])]
    public function createTerminal(
        string $clientId,
        Request $request,
        RegisterTerminalHandler $registerTerminal,
    ): Response {
        $serial       = trim((string) $request->request->get('serial', ''));
        $vendor       = trim((string) $request->request->get('vendor', ''));
        $funcionality = trim((string) $request->request->get('funcionality', ''));
        $type         = trim((string) $request->request->get('type', ''));
        $atmModelCode = trim((string) $request->request->get('atmModelCode', ''));

        try {
            ($registerTerminal)(new RegisterTerminalCommand(
                clientId:      $clientId,
                serial:        $serial,
                vendor:        $vendor,
                funcionality:  $funcionality,
                type:          $type,
                atmModelCode:  $atmModelCode !== '' ? $atmModelCode : null,
            ));

            $this->addFlash('success', 'Terminal registrado exitosamente.');

            return $this->redirectToRoute('client_show', ['id' => $clientId]);
        } catch (ClientNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (SerialForTerminalAlreadyExistsException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('client/terminal/form.html.twig', [
            'clientId' => $clientId,
            'errors'   => [],
            'input'    => compact('serial', 'vendor', 'funcionality', 'type', 'atmModelCode'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('terminal.view')]
    #[Route('/{id}', name: 'client_terminal_show', methods: ['GET'])]
    public function showTerminal(
        string $clientId,
        string $id,
        FindTerminalByIdHandler $findTerminal,
    ): Response {
        try {
            $terminal = ($findTerminal)(new FindTerminalByIdQuery($id));
        } catch (TerminalNotFoundException) {
            throw $this->createNotFoundException('Terminal no encontrado.');
        }

        if ($terminal->clientId !== $clientId) {
            throw $this->createNotFoundException('Terminal no encontrado para este cliente.');
        }

        return $this->render('client/terminal/show.html.twig', [
            'clientId' => $clientId,
            'terminal' => $terminal,
        ]);
    }

    #[IsGranted('terminal.delete')]
    #[Route('/{id}/delete', name: 'client_terminal_delete', methods: ['POST'])]
    public function deleteTerminal(
        string $clientId,
        string $id,
        Request $request,
        SoftDeleteTerminalHandler $deleteTerminal,
    ): Response {
        $reason = trim((string) $request->request->get('reason', ''));

        try {
            ($deleteTerminal)(new SoftDeleteTerminalCommand($id, $reason));
            $this->addFlash('success', 'Terminal eliminado lógicamente.');
        } catch (TerminalNotFoundException) {
            throw $this->createNotFoundException('Terminal no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('terminal.update')]
    #[Route('/{id}/deactivate', name: 'client_terminal_deactivate', methods: ['POST'])]
    public function deactivateTerminal(
        string $clientId,
        string $id,
        DeactivateTerminalHandler $deactivateTerminal,
    ): Response {
        try {
            ($deactivateTerminal)(new DeactivateTerminalCommand($id));
            $this->addFlash('success', 'Terminal desactivado exitosamente.');
        } catch (TerminalNotFoundException) {
            throw $this->createNotFoundException('Terminal no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_terminal_list', ['clientId' => $clientId]);
    }

    #[IsGranted('terminal.update')]
    #[Route('/{id}/reactivate', name: 'client_terminal_reactivate', methods: ['POST'])]
    public function reactivateTerminal(
        string $clientId,
        string $id,
        ActivateTerminalHandler $activateTerminal,
    ): Response {
        try {
            ($activateTerminal)(new ActivateTerminalCommand($id));
            $this->addFlash('success', 'Terminal reactivado exitosamente.');
        } catch (TerminalNotFoundException) {
            throw $this->createNotFoundException('Terminal no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_terminal_list', ['clientId' => $clientId]);
    }
}
