<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Console;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:inventory:backfill-backpacks',
    description: 'Crea mochilas faltantes para técnicos existentes que no tienen una.',
)]
final class BackfillTechnicianBackpacksCommand extends Command
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo muestra qué mochilas se crearían sin persistir.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Backfill: Mochilas para técnicos');

        $allEmployees = $this->employeeRepository->findAll();
        $technicians = array_filter(
            $allEmployees,
            static fn(Employee $e) => $e->isTechnician(),
        );

        if (count($technicians) === 0) {
            $io->info('No se encontraron técnicos en el sistema.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Evaluando %d técnicos...', count($technicians)));

        $created = 0;
        $skipped = 0;

        foreach ($technicians as $employee) {
            $employeeId = (string) $employee->id();
            $existing = $this->locationRepository->findByEmployeeId($employeeId);
            $alreadyHasBackpack = false;

            foreach ($existing as $location) {
                if ($location->isBackpack() && !$location->isDeleted()) {
                    $alreadyHasBackpack = true;
                    break;
                }
            }

            if ($alreadyHasBackpack) {
                $io->writeln(sprintf('  <comment>↷ Ya tiene</comment>  %s %s (%s)', $employee->firstName(), $employee->lastName(), $employeeId));
                $skipped++;
                continue;
            }

            $backpackName = sprintf('Mochila - %s %s', $employee->firstName(), $employee->lastName());

            if ($dryRun) {
                $io->writeln(sprintf('  <info>✓ Crearía</info>   %s → %s', $backpackName, $employeeId));
                $created++;
                continue;
            }

            $backpack = Location::create(
                id: new LocationIdValueObject($this->idGenerator->generate()),
                name: new LocationNameValueObject($backpackName),
                city: '',
                address: '',
                type: LocationType::BACKPACK,
                employeeId: $employeeId,
            );

            $this->locationRepository->save($backpack);
            $io->writeln(sprintf('  <info>✓ Creada</info>    %s → %s', $backpackName, $employeeId));
            $created++;
        }

        $io->newLine();

        if ($dryRun) {
            $io->success(sprintf(
                '%d mochilas se crearían, %d técnicos ya tenían una. (dry-run)',
                $created,
                $skipped,
            ));
        } else {
            $io->success(sprintf(
                '%d mochilas creadas, %d técnicos ya tenían una.',
                $created,
                $skipped,
            ));
        }

        return Command::SUCCESS;
    }
}
