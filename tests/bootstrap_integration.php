<?php

declare(strict_types=1);

/**
 * Bootstrap para tests de integración.
 *
 * Carga el kernel en entorno test y crea/esquema de base de datos
 * utilizando el mismo EntityManager configurado en config/packages/doctrine.yaml.
 *
 * Uso: vendor/bin/phpunit --bootstrap tests/bootstrap_integration.php tests/Integration/
 */

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

// Boot kernel en entorno test
$kernel = new Kernel('test', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

// Crear/esquema de BD — solo las tablas de las entidades mapeadas
// Ejecuta doctrine:schema:create --quiet para evitar output
$input = new ArrayInput([
    'command' => 'doctrine:schema:create',
    '--quiet' => true,
]);

$application->run($input);

$kernel->shutdown();
