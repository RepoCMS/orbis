<?php

declare(strict_types=1);

namespace App\Support\Console;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\AtmModel\AtmModel;
use App\Support\Domain\AtmModel\AtmModuleNode;
use App\Support\Domain\AtmModel\AtmModuleTreeData;
use App\Support\Domain\Entities\ModuleNode;
use App\Support\Domain\Repositories\ModuleNodeRepositoryInterface;
use App\Support\Domain\ValueObjects\ModuleNodeIdValueObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'support:seed-module-tree',
    description: 'Siembra en BD el árbol de módulos ATM desde las definiciones estáticas.',
)]
final class SeedModuleTreeCommand extends Command
{
    public function __construct(
        private readonly ModuleNodeRepositoryInterface $repository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simula sin persistir.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $io->title('Siembra del árbol de módulos ATM');

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach (AtmModel::cases() as $model) {
            $existingRoots = $this->repository->findRootsByModel($model);

            if ($existingRoots !== []) {
                $io->writeln(sprintf('  <comment>↷ Ya seedeado</comment> %s', $model->label()));
                $totalSkipped++;
                continue;
            }

            $tree = $this->getHardcodedTree($model);

            if (!$isDryRun) {
                $this->seedNodes($tree, $model, null);
            }

            $io->writeln(sprintf('  <info>✓ Sembrado</info>   %s (%d nodos raíz)', $model->label(), count($tree)));
            $totalCreated++;
        }

        $io->newLine();
        $io->success(sprintf('%d modelos sembrados, %d ya existían.', $totalCreated, $totalSkipped));

        return Command::SUCCESS;
    }

    /**
     * @return AtmModuleNode[]
     */
    private function getHardcodedTree(AtmModel $model): array
    {
        $tree = AtmModuleTreeData::commonModules();

        if ($model->isRecycler()) {
            $tree[] = AtmModuleTreeData::recyclerModules();
            $tree[] = AtmModuleTreeData::documentDepositModules();
        } else {
            $tree[] = AtmModuleTreeData::dispenserModules();
        }

        return $tree;
    }

    /**
     * @param AtmModuleNode[] $nodes
     */
    private function seedNodes(array $nodes, AtmModel $model, ?ModuleNode $parent, int &$position = 0): void
    {
        foreach ($nodes as $atmNode) {
            $entity = ModuleNode::create(
                id: ModuleNodeIdValueObject::fromString($this->idGenerator->generate()),
                model: $model,
                code: $atmNode->code,
                label: $atmNode->label,
                components: $atmNode->components,
                parent: $parent,
                position: $position++,
            );

            $this->repository->save($entity);

            if ($atmNode->children !== []) {
                $childPosition = 0;
                $this->seedNodes($atmNode->children, $model, $entity, $childPosition);
            }
        }
    }
}
