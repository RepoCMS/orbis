<?php

declare(strict_types=1);

namespace App\Support\Presentation\Http;

use App\Support\Application\Command\CreateModuleNode\CreateModuleNodeCommand;
use App\Support\Application\Command\CreateModuleNode\CreateModuleNodeHandler;
use App\Support\Application\Command\DeleteModuleNode\DeleteModuleNodeCommand;
use App\Support\Application\Command\DeleteModuleNode\DeleteModuleNodeHandler;
use App\Support\Application\Command\UpdateModuleNode\UpdateModuleNodeCommand;
use App\Support\Application\Command\UpdateModuleNode\UpdateModuleNodeHandler;
use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeHandler;
use App\Support\Application\Query\GetAtmModuleTree\GetAtmModuleTreeQuery;
use App\Support\Application\Query\Response\ModuleTreeResponse;
use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\Exceptions\ModuleNodeNotFoundException;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support/module-tree', name: 'support_module_tree_')]
final class AtmModuleTreeController extends AbstractController
{
    #[IsGranted('support.view')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        GetAtmModuleTreeHandler $handler,
    ): Response {
        $modelValue = $request->query->get('model', 'mx8200st');
        $model = AtmModel::tryFrom($modelValue);
        $models = AtmModel::cases();

        $tree = [];
        if ($model !== null) {
            try {
                $tree = $handler(new GetAtmModuleTreeQuery($model->value));
            } catch (\InvalidArgumentException) {
                $this->addFlash('error', 'Modelo de ATM no válido.');
            }
        }

        return $this->render('support/module_tree/list.html.twig', [
            'models' => $models,
            'selectedModel' => $model,
            'tree' => $tree,
        ]);
    }

    #[IsGranted('support.view')]
    #[Route('/{code}', name: 'show', methods: ['GET'])]
    public function show(
        string $code,
        Request $request,
        GetAtmModuleTreeHandler $handler,
    ): Response {
        $modelValue = $request->query->get('model', 'mx8200st');
        $model = AtmModel::tryFrom($modelValue);
        if ($model === null) {
            throw $this->createNotFoundException('Modelo de ATM no válido.');
        }

        $nodes = $handler(new GetAtmModuleTreeQuery($model->value));
        $node = $this->findNodeByCode($code, $nodes);

        if ($node === null) {
            throw $this->createNotFoundException('Nodo no encontrado.');
        }

        $parent = $this->findParentNode($code, $nodes);

        return $this->render('support/module_tree/show.html.twig', [
            'node' => $node,
            'parent' => $parent,
            'model' => $model,
        ]);
    }

    #[IsGranted('support.manage_modules')]
    #[Route('/{code}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        string $code,
        Request $request,
        GetAtmModuleTreeHandler $handler,
        UpdateModuleNodeHandler $updateHandler,
        ModuleNodeRepositoryInterface $moduleNodeRepo,
    ): Response {
        $modelValue = $request->query->get('model', 'mx8200st');
        $model = AtmModel::tryFrom($modelValue);
        if ($model === null) {
            throw $this->createNotFoundException('Modelo de ATM no válido.');
        }

        if ($request->isMethod('POST')) {
            $dbNode = $moduleNodeRepo->findByCodeAndModel($code, $model);

            if ($dbNode === null) {
                $this->addFlash('info', 'Este nodo pertenece al árbol por defecto. Ejecute "support:seed-module-tree" para habilitar la edición.');

                return $this->redirectToRoute('support_module_tree_show', [
                    'code' => $code,
                    'model' => $model->value,
                ]);
            }

            $label = $request->request->get('label', '');
            $components = array_filter(explode("\n", $request->request->get('components', '')));

            try {
                ($updateHandler)(new UpdateModuleNodeCommand(
                    id: (string) $dbNode->id(),
                    label: $label,
                    components: $components,
                ));
                $this->addFlash('success', 'Nodo actualizado correctamente.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (ModuleNodeNotFoundException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('support_module_tree_show', [
                'code' => $code,
                'model' => $model->value,
            ]);
        }

        $nodes = $handler(new GetAtmModuleTreeQuery($model->value));
        $node = $this->findNodeByCode($code, $nodes);

        if ($node === null) {
            throw $this->createNotFoundException('Nodo no encontrado.');
        }

        return $this->render('support/module_tree/form.html.twig', [
            'node' => $node,
            'model' => $model,
            'isEdit' => true,
        ]);
    }

    #[IsGranted('support.manage_modules')]
    #[Route('/{code}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        string $code,
        Request $request,
        DeleteModuleNodeHandler $deleteHandler,
        ModuleNodeRepositoryInterface $moduleNodeRepo,
    ): Response {
        $modelValue = $request->query->get('model', 'mx8200st');
        $model = AtmModel::tryFrom($modelValue);
        if ($model === null) {
            throw $this->createNotFoundException('Modelo de ATM no válido.');
        }

        if (!$this->isCsrfTokenValid('delete-module-node', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');

            return $this->redirectToRoute('support_module_tree_list', ['model' => $model->value]);
        }

        $dbNode = $moduleNodeRepo->findByCodeAndModel($code, $model);

        if ($dbNode === null) {
            $this->addFlash('info', 'Este nodo pertenece al árbol por defecto. Ejecute "support:seed-module-tree" para habilitar la eliminación.');

            return $this->redirectToRoute('support_module_tree_list', ['model' => $model->value]);
        }

        try {
            ($deleteHandler)(new DeleteModuleNodeCommand(
                id: (string) $dbNode->id(),
            ));
            $this->addFlash('success', 'Nodo eliminado correctamente.');
        } catch (ModuleNodeNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('support_module_tree_list', ['model' => $model->value]);
    }

    #[IsGranted('support.manage_modules')]
    #[Route('/{code}/add-child', name: 'add_child', methods: ['GET', 'POST'])]
    public function addChild(
        string $code,
        Request $request,
        GetAtmModuleTreeHandler $handler,
        CreateModuleNodeHandler $createHandler,
        ModuleNodeRepositoryInterface $moduleNodeRepo,
    ): Response {
        $modelValue = $request->query->get('model', 'mx8200st');
        $model = AtmModel::tryFrom($modelValue);
        if ($model === null) {
            throw $this->createNotFoundException('Modelo de ATM no válido.');
        }

        $parentNode = $moduleNodeRepo->findByCodeAndModel($code, $model);

        if ($request->isMethod('POST')) {
            if ($parentNode === null) {
                $this->addFlash('info', 'El padre pertenece al árbol por defecto. Ejecute "support:seed-module-tree" para habilitar la creación.');

                return $this->redirectToRoute('support_module_tree_list', ['model' => $model->value]);
            }

            $label = $request->request->get('label', '');
            $newCode = $request->request->get('code', '');
            $components = array_filter(explode("\n", $request->request->get('components', '')));

            try {
                ($createHandler)(new CreateModuleNodeCommand(
                    model: $model->value,
                    code: $newCode,
                    label: $label,
                    components: $components,
                    parentId: (string) $parentNode->id(),
                ));
                $this->addFlash('success', 'Nodo creado correctamente.');

                return $this->redirectToRoute('support_module_tree_show', [
                    'code' => $newCode,
                    'model' => $model->value,
                ]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (ModuleNodeNotFoundException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('support_module_tree_list', ['model' => $model->value]);
        }

        $nodes = $handler(new GetAtmModuleTreeQuery($model->value));
        $node = $this->findNodeByCode($code, $nodes);

        if ($node === null) {
            throw $this->createNotFoundException('Nodo no encontrado.');
        }

        return $this->render('support/module_tree/form.html.twig', [
            'node' => $node,
            'model' => $model,
            'isEdit' => false,
            'parentCode' => $code,
        ]);
    }

    /**
     * @param ModuleTreeResponse[] $nodes
     */
    private function findNodeByCode(string $code, array $nodes): ?ModuleTreeResponse
    {
        foreach ($nodes as $node) {
            if ($node->code === $code) {
                return $node;
            }
            if (!empty($node->children)) {
                $found = $this->findNodeByCode($code, $node->children);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param ModuleTreeResponse[] $nodes
     */
    private function findParentNode(string $code, array $nodes): ?ModuleTreeResponse
    {
        foreach ($nodes as $node) {
            foreach ($node->children as $child) {
                if ($child->code === $code) {
                    return $node;
                }
                if (!empty($child->children)) {
                    $found = $this->findParentNode($code, $child->children);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }
}
