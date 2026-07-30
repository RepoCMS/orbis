<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Support\Application\Command\RecordModuleFinding\RecordModuleFindingCommand;
use App\Support\Application\Command\RecordModuleFinding\RecordModuleFindingHandler;
use App\Support\Application\Command\UpdateModuleFinding\UpdateModuleFindingCommand;
use App\Support\Application\Command\UpdateModuleFinding\UpdateModuleFindingHandler;
use App\Support\Application\Command\UploadFindingEvidence\UploadFindingEvidenceCommand;
use App\Support\Application\Command\UploadFindingEvidence\UploadFindingEvidenceHandler;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeQuery;
use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeHandler;
use App\Support\Application\Query\ListTicketModuleFindings\ListTicketModuleFindingsQuery;
use App\Support\Application\Query\ListTicketModuleFindings\ListTicketModuleFindingsHandler;
use App\Support\Domain\Exceptions\TicketModuleFindingException;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/support')]
final class ApiSupportTicketModuleFindingController extends AbstractController
{
    #[IsGranted('ticket.view')]
    #[Route('/atm-module-tree', name: 'api_support_atm_module_tree', methods: ['GET'])]
    public function getAtmModuleTree(
        Request $request,
        GetAtmModuleTreeHandler $handler,
    ): JsonResponse {
        $model = trim((string) $request->query->get('model', ''));
        if ($model === '') {
            return $this->json(['error' => 'model is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tree = $handler(new GetAtmModuleTreeQuery($model));

            return $this->json(['data' => $tree]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.view')]
    #[Route('/tickets/{ticketId}/module-findings', name: 'api_support_tickets_module_findings_list', methods: ['GET'])]
    public function listModuleFindings(
        string $ticketId,
        ListTicketModuleFindingsHandler $handler,
    ): JsonResponse {
        try {
            $findings = $handler(new ListTicketModuleFindingsQuery($ticketId));

            return $this->json(['data' => $findings]);
        } catch (ApplicationException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/tickets/{ticketId}/module-findings', name: 'api_support_tickets_module_findings_create', methods: ['POST'])]
    public function recordModuleFinding(
        string $ticketId,
        Request $request,
        RecordModuleFindingHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Cuerpo de la solicitud inválido.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $command = new RecordModuleFindingCommand(
                ticketId: $ticketId,
                modulePath: trim((string) ($payload['modulePath'] ?? '')),
                componentName: trim((string) ($payload['componentName'] ?? '')),
                findingType: trim((string) ($payload['findingType'] ?? '')),
                registeredBy: $this->getCurrentUserId(),
                notes: isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            );

            $findingId = $handler($command);

            return $this->json(['data' => ['id' => $findingId]], Response::HTTP_CREATED);
        } catch (TicketNotFoundException | ApplicationException $e) {
            return $this->json(['error' => 'Ticket no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid finding type'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/tickets/{ticketId}/module-findings/{findingId}', name: 'api_support_tickets_module_findings_update', methods: ['PATCH'])]
    public function updateModuleFinding(
        string $ticketId,
        string $findingId,
        Request $request,
        UpdateModuleFindingHandler $handler,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Cuerpo de la solicitud inválido.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $handler(new UpdateModuleFindingCommand(
                findingId: $findingId,
                findingType: trim((string) ($payload['findingType'] ?? '')),
                notes: isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ApplicationException $e) {
            return $this->json(['error' => 'Module finding no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid finding type'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/tickets/{ticketId}/module-findings/{findingId}', name: 'api_support_tickets_module_findings_delete', methods: ['DELETE'])]
    public function deleteModuleFinding(
        string $ticketId,
        string $findingId,
        TicketModuleFindingRepositoryInterface $repository,
    ): JsonResponse {
        $id = TicketModuleFindingIdValueObject::fromString($findingId);
        $finding = $repository->findById($id);

        if ($finding === null) {
            return $this->json(['error' => 'Module finding no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $repository->delete($finding);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/tickets/{ticketId}/module-findings/{findingId}/evidence', name: 'api_support_tickets_module_findings_upload_evidence', methods: ['POST'])]
    public function uploadEvidence(
        string $findingId,
        Request $request,
        UploadFindingEvidenceHandler $handler,
    ): JsonResponse {
        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(
                ['error' => 'El archivo es obligatorio'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/findings';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $filename = sprintf(
            '%s_%s.%s',
            $findingId,
            uniqid('', true),
            $file->guessExtension() ?? 'jpg'
        );
        $file->move($uploadsDir, $filename);
        $relativePath = 'uploads/findings/' . $filename;
        $moment = trim((string) $request->request->get('moment', ''));

        try {
            $command = new UploadFindingEvidenceCommand(
                findingId: $findingId,
                filePath: $relativePath,
                originalName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                moment: $moment,
                uploadedBy: $this->getCurrentUserId(),
            );

            $evidenceId = $handler($command);

            return $this->json(['data' => ['id' => $evidenceId]], Response::HTTP_CREATED);
        } catch (ApplicationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid evidence moment'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ticket.assistance')]
    #[Route('/tickets/{ticketId}/module-findings/{findingId}/evidence/{evidenceId}', name: 'api_support_tickets_module_findings_delete_evidence', methods: ['DELETE'])]
    public function deleteEvidence(
        string $ticketId,
        string $findingId,
        string $evidenceId,
        TicketModuleFindingRepositoryInterface $repository,
    ): JsonResponse {
        $findingIdVo = TicketModuleFindingIdValueObject::fromString($findingId);
        $finding = $repository->findById($findingIdVo);

        if ($finding === null) {
            return $this->json(['error' => 'Module finding no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $evidenceIdVo = FindingEvidenceIdValueObject::fromString($evidenceId);
        $finding->removeEvidence($evidenceIdVo);
        $repository->save($finding);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getCurrentUserId(): string
    {
        $user = $this->getUser();
        if ($user instanceof SymfonySecurityUser) {
            return (string) $user->getDomainUser()->id();
        }

        return '';
    }
}
