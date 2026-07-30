<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Console;

use App\Location\Domain\Entities\City;
use App\Location\Domain\Entities\Province;
use App\Location\Domain\Entities\Region;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\Repositories\ProvinceRepositoryInterface;
use App\Location\Domain\Repositories\RegionRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:geography',
    description: 'Siembra el catálogo geográfico de Ecuador (regiones, provincias, ciudades).',
)]
final class SeedGeographyCommand extends Command
{
    /** @var array<string, array<string, string[]>> */
    private const DATA = [
        'Costa' => [
            'Guayas'                          => ['Guayaquil', 'Samborondón', 'Durán', 'Milagro', 'Daule'],
            'Manabí'                          => ['Portoviejo', 'Manta', 'Chone', 'Bahía de Caráquez'],
            'Los Ríos'                        => ['Babahoyo', 'Quevedo', 'Ventanas'],
            'El Oro'                          => ['Machala', 'Pasaje', 'Santa Rosa'],
            'Esmeraldas'                      => ['Esmeraldas', 'Atacames'],
            'Santa Elena'                     => ['Santa Elena', 'Salinas', 'La Libertad'],
            'Santo Domingo de los Tsáchilas'  => ['Santo Domingo'],
        ],
        'Sierra' => [
            'Pichincha'   => ['Quito', 'Cayambe', 'Sangolquí', 'Machachi'],
            'Azuay'       => ['Cuenca', 'Gualaceo', 'Paute'],
            'Tungurahua'  => ['Ambato', 'Baños', 'Pelileo'],
            'Chimborazo'  => ['Riobamba', 'Alausí'],
            'Imbabura'    => ['Ibarra', 'Otavalo', 'Cotacachi'],
            'Cotopaxi'    => ['Latacunga', 'La Maná'],
            'Bolívar'     => ['Guaranda'],
            'Cañar'       => ['Azogues', 'La Troncal'],
            'Carchi'      => ['Tulcán'],
            'Loja'        => ['Loja', 'Catamayo'],
        ],
        'Amazonía' => [
            'Sucumbíos'        => ['Nueva Loja'],
            'Napo'             => ['Tena'],
            'Pastaza'          => ['Puyo'],
            'Morona Santiago'  => ['Macas'],
            'Zamora Chinchipe' => ['Zamora'],
            'Orellana'         => ['Francisco de Orellana'],
        ],
        'Insular' => [
            'Galápagos' => ['Puerto Ayora', 'Puerto Baquerizo Moreno'],
        ],
    ];

    public function __construct(
        private readonly RegionRepositoryInterface   $regionRepository,
        private readonly ProvinceRepositoryInterface $provinceRepository,
        private readonly CityRepositoryInterface     $cityRepository,
        private readonly IdGeneratorInterface        $idGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Siembra geográfica de Ecuador');

        if (count($this->regionRepository->findAll()) > 0) {
            $io->success('Los datos geográficos ya existen. Nada que hacer.');
            return Command::SUCCESS;
        }

        foreach (self::DATA as $regionName => $provinces) {
            $region = Region::reconstitute($this->idGenerator->generate(), $regionName);
            $this->regionRepository->save($region);

            foreach ($provinces as $provinceName => $cities) {
                $province = Province::reconstitute(
                    $this->idGenerator->generate(),
                    (string) $region->id(),
                    $provinceName,
                );
                $this->provinceRepository->save($province);

                foreach ($cities as $cityName) {
                    $city = City::reconstitute(
                        $this->idGenerator->generate(),
                        (string) $province->id(),
                        $cityName,
                    );
                    $this->cityRepository->save($city);
                }
            }

            $io->writeln(sprintf('  <info>✓</info> %s', $regionName));
        }

        $io->success('Datos geográficos sembrados correctamente.');
        return Command::SUCCESS;
    }
}
