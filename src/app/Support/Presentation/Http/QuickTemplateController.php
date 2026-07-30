<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/quick-template', name: 'support_quick_template_')]
final class QuickTemplateController extends AbstractController
{
    #[IsGranted('quick_template.view')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        ListQuickTemplatesHandler $handler,
    ): Response {
        $templates = $handler(new ListQuickTemplatesQuery());

        return $this->render('support/quick_template/list.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[IsGranted('quick_template.create')]
    #[Route('/new', name: 'new', methods: ['GET'])]
    public function new(
        TerminalModelRepositoryInterface $modelRepo,
    ): Response {
        $models = $modelRepo->findAll();

        return $this->render('support/quick_template/form.html.twig', [
            'models'   => $models,
            'template' => null,
        ]);
    }

    #[IsGranted('quick_template.create')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateQuickTemplateHandler $handler,
    ): Response {
        /** @var SymfonySecurityUser $user */
        $user = $this->getUser();

        try {
            $handler(new CreateQuickTemplateCommand(
                modelId:     (string) $request->request->get('modelId'),
                name:        (string) $request->request->get('name'),
                createdBy:   (string) $user->getDomainUser()->id(),
                description: $request->request->get('description') ?: null,
                items:       [],
            ));

            $this->addFlash('success', 'Plantilla rápida creada correctamente.');

            return $this->redirectToRoute('support_quick_template_list');
        } catch (DomainException | ApplicationException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('support_quick_template_new');
        }
    }

    #[IsGranted('quick_template.view')]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        string $id,
        FindQuickTemplateHandler $handler,
    ): Response {
        try {
            $template = $handler(new FindQuickTemplateQuery(templateId: $id));
        } catch (QuickTemplateNotFoundException | ApplicationException | \InvalidArgumentException) {
            throw $this->createNotFoundException('Plantilla rápida no encontrada.');
        }

        return $this->render('support/quick_template/show.html.twig', [
            'template' => $template,
        ]);
    }

    #[IsGranted('quick_template.edit')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET'])]
    public function edit(
        string $id,
        FindQuickTemplateHandler $handler,
        TerminalModelRepositoryInterface $modelRepo,
    ): Response {
        try {
            $template = $handler(new FindQuickTemplateQuery(templateId: $id));
        } catch (QuickTemplateNotFoundException | ApplicationException | \InvalidArgumentException) {
            throw $this->createNotFoundException('Plantilla rápida no encontrada.');
        }

        $models = $modelRepo->findAll();

        return $this->render('support/quick_template/form.html.twig', [
            'models'   => $models,
            'template' => $template,
        ]);
    }

    #[IsGranted('quick_template.edit')]
    #[Route('/{id}', name: 'update', methods: ['POST'])]
    public function update(
        string $id,
        Request $request,
        UpdateQuickTemplateHandler $handler,
    ): Response {
        try {
            $handler(new UpdateQuickTemplateCommand(
                templateId:  $id,
                name:        $request->request->get('name') ?: null,
                description: $request->request->has('description') ? ($request->request->get('description') ?: null) : null,
                isActive:    $request->request->has('isActive') ? (bool) $request->request->get('isActive') : null,
            ));

            $this->addFlash('success', 'Plantilla rápida actualizada correctamente.');

            return $this->redirectToRoute('support_quick_template_show', ['id' => $id]);
        } catch (QuickTemplateNotFoundException | ApplicationException $e) {
            $this->addFlash('error', 'Plantilla rápida no encontrada.');

            return $this->redirectToRoute('support_quick_template_list');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('support_quick_template_edit', ['id' => $id]);
        }
    }

    #[IsGranted('quick_template.delete')]
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        string $id,
        DeleteQuickTemplateHandler $handler,
    ): Response {
        try {
            $handler(new DeleteQuickTemplateCommand(templateId: $id));

            $this->addFlash('success', 'Plantilla rápida eliminada correctamente.');
        } catch (QuickTemplateNotFoundException | ApplicationException | \InvalidArgumentException $e) {
            $this->addFlash('error', 'Plantilla rápida no encontrada.');
        }

        return $this->redirectToRoute('support_quick_template_list');
    }
}
