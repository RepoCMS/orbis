<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use App\Support\Application\Command\CreateQuickTemplate\CreateQuickTemplateCommand;
use App\Support\Application\Command\CreateQuickTemplate\CreateQuickTemplateHandler;
use App\Support\Application\Command\DeleteQuickTemplate\DeleteQuickTemplateCommand;
use App\Support\Application\Command\DeleteQuickTemplate\DeleteQuickTemplateHandler;
use App\Support\Application\Command\UpdateQuickTemplate\UpdateQuickTemplateCommand;
use App\Support\Application\Command\UpdateQuickTemplate\UpdateQuickTemplateHandler;
use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Application\Query\FindQuickTemplate\FindQuickTemplateHandler;
use App\Support\Application\Query\FindQuickTemplate\FindQuickTemplateQuery;
use App\Support\Application\Query\ListQuickTemplates\ListQuickTemplatesHandler;
use App\Support\Application\Query\ListQuickTemplates\ListQuickTemplatesQuery;
use App\Support\Application\Query\ListQuickTemplatesByModel\ListQuickTemplatesByModelHandler;
use App\Support\Application\Query\ListQuickTemplatesByModel\ListQuickTemplatesByModelQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/support/quick-templates')]
final class ApiSupportQuickTemplateController extends AbstractController
{
    #[IsGranted('quick_template.view')]
    #[Route('', name: 'api_support_quick_templates_list', methods: ['GET'])]
    public function list(
        ListQuickTemplatesHandler $handler,
    ): JsonResponse {
        $templates = $handler(new ListQuickTemplatesQuery());

        return $this->json(['data' => $templates]);
    }

    #[IsGranted('quick_template.view')]
    #[Route('/by-model/{modelId}', name: 'api_support_quick_templates_by_model', methods: ['GET'])]
    public function listByModel(
        string $modelId,
        Request $request,
        ListQuickTemplatesByModelHandler $handler,
    ): JsonResponse {
        $onlyActive = (bool) $request->query->get('onlyActive', true);

        $templates = $handler(new ListQuickTemplatesByModelQuery(
            modelId:    $modelId,
            onlyActive: $onlyActive,
        ));

        return $this->json(['data' => $templates]);
    }

    #[IsGranted('quick_template.view')]
    #[Route('/{id}', name: 'api_support_quick_templates_show', methods: ['GET'])]
    public function show(
        string $id,
        FindQuickTemplateHandler $handler,
    ): JsonResponse {
        try {
            $template = $handler(new FindQuickTemplateQuery(templateId: $id));

            return $this->json(['data' => $template]);
        } catch (QuickTemplateNotFoundException | \InvalidArgumentException $e) {
            return $this->json(['error' => 'Plantilla rápida no encontrada.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('quick_template.create')]
    #[Route('', name: 'api_support_quick_templates_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateQuickTemplateHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new CreateQuickTemplateCommand(
                modelId:     trim((string) ($payload['modelId'] ?? '')),
                name:        trim((string) ($payload['name'] ?? '')),
                createdBy:   (string) $user->getDomainUser()->id(),
                description: isset($payload['description']) ? trim((string) $payload['description']) : null,
                items:       (array) ($payload['items'] ?? []),
            ));

            return $this->json(['data' => ['message' => 'Plantilla rápida creada correctamente.']], Response::HTTP_CREATED);
        } catch (DomainException | ApplicationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('quick_template.edit')]
    #[Route('/{id}', name: 'api_support_quick_templates_update', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdateQuickTemplateHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $handler(new UpdateQuickTemplateCommand(
                templateId:  $id,
                name:        isset($payload['name']) ? trim((string) $payload['name']) : null,
                description: array_key_exists('description', $payload)
                    ? (isset($payload['description']) ? trim((string) $payload['description']) : null)
                    : null,
                isActive:    array_key_exists('isActive', $payload)
                    ? (bool) $payload['isActive']
                    : null,
            ));

            return $this->json(['data' => ['message' => 'Plantilla rápida actualizada correctamente.']]);
        } catch (QuickTemplateNotFoundException $e) {
            return $this->json(['error' => 'Plantilla rápida no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | ApplicationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('quick_template.delete')]
    #[Route('/{id}', name: 'api_support_quick_templates_delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteQuickTemplateHandler $handler,
    ): JsonResponse {
        try {
            $handler(new DeleteQuickTemplateCommand(templateId: $id));

            return $this->json(['data' => ['message' => 'Plantilla rápida eliminada correctamente.']]);
        } catch (QuickTemplateNotFoundException $e) {
            return $this->json(['error' => 'Plantilla rápida no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
