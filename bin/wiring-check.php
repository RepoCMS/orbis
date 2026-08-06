<?php

declare(strict_types=1);

/**
 * Wiring check — regla 11 del Apéndice A de AGENTS.md.
 *
 * Verifica que todo Command, Query, Domain Event y Subscriber tiene al menos
 * un caller real fuera de su propia definición (handler pareja, tests y
 * bridge/abstracts no cuentan).
 *
 * Uso: php bin/wiring-check.php
 * Exit 0 = 0 huérfanos · Exit 1 = lista de huérfanos (falla el gate CI).
 */

$root = dirname(__DIR__);
$src = $root.'/src';
$config = $root.'/config';

$allFiles = array_merge(
    findFiles($src, '*.php'),
    findFiles($config, '*.php'),
    findFiles($config, '*.yaml'),
    findFiles($config, '*.yml'),
);

/** @var array<string, string> $contents Archivo => contenido */
$contents = [];
foreach ($allFiles as $file) {
    $contents[$file] = (string) file_get_contents($file);
}

/** @var array<string, string> $shortNameCount nombre corto => cantidad de clases con ese nombre */
$shortNameCount = [];
$targets = [];

// 1. Commands
foreach (findFiles($src.'/app', '*Command.php') as $file) {
    $targets[] = buildTarget($file, $contents, 'Command');
}

// 2. Queries
foreach (findFiles($src.'/app', '*Query.php') as $file) {
    $targets[] = buildTarget($file, $contents, 'Query');
}

// 3. Domain Events ({BC}/Domain/Events/*.php)
foreach (findFiles($src.'/app', '*.php') as $file) {
    if (str_contains($file, '/Domain/Events/')) {
        $targets[] = buildTarget($file, $contents, 'Event');
    }
}

// 4. Subscribers
foreach (findFiles($src.'/app', '*Subscriber.php') as $file) {
    $targets[] = buildTarget($file, $contents, 'Subscriber');
}

foreach ($targets as $t) {
    $short = $t['short'];
    $shortNameCount[$short] = ($shortNameCount[$short] ?? 0) + 1;
}

$orphans = [];
foreach ($targets as $t) {
    if (isCliCommand($t['file'])) {
        continue; // CLI commands: caller = registro console.command en services.yaml (framework)
    }
    if (isEventSubscriber($t['file'], $contents)) {
        continue; // EventSubscriberInterface + autoconfigure: true en services.yaml (framework)
    }
    if (!hasCaller($t, $contents, $shortNameCount)) {
        $orphans[] = sprintf('%s (%s)', $t['fqcn'], str_replace($root, '.', $t['file']));
    }
}

if ($orphans === []) {
    fwrite(STDOUT, sprintf("Wiring OK: %d targets verificados, 0 huerfanos.\n", count($targets)));
    exit(0);
}

fwrite(STDERR, sprintf("Wiring FAIL: %d de %d targets sin caller real:\n", count($orphans), count($targets)));
foreach ($orphans as $o) {
    fwrite(STDERR, "  - $o\n");
}
exit(1);

/**
 * @return array{file: string, fqcn: string, short: string, kind: string}
 */
function buildTarget(string $file, array $contents, string $kind): array
{
    $content = $contents[$file] ?? (string) file_get_contents($file);
    $namespace = '';
    if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
        $namespace = trim($m[1]);
    }
    $class = '';
    if (preg_match('/\b(class|interface)\s+([A-Za-z0-9_]+)/', $content, $m)) {
        $class = $m[2];
    }
    $fqcn = $namespace !== '' && $class !== '' ? $namespace.'\\'.$class : $class;

    return ['file' => $file, 'fqcn' => $fqcn, 'short' => $class, 'kind' => $kind];
}

/**
 * @param array{file: string, fqcn: string, short: string, kind: string} $target
 * @param array<string, string> $contents
 * @param array<string, int> $shortNameCount
 */
function hasCaller(array $target, array $contents, array $shortNameCount): bool
{
    $file = $target['file'];
    $fqcn = $target['fqcn'];
    $short = $target['short'];

    if ($fqcn === '' || $short === '') {
        return true; // archivo sin clase (no verificable) — no bloquear
    }

    foreach ($contents as $otherFile => $content) {
        $otherFileNorm = str_replace('\\', '/', $otherFile);
        if ($otherFileNorm === str_replace('\\', '/', $file)) {
            continue;
        }
        $basename = basename($file);
        if ($target['kind'] !== 'Subscriber' && str_contains(basename($otherFile), $basename)) {
            continue; // Handler pareja ({Name}Command.php ↔ {Name}Handler.php)
        }
        if (str_contains($otherFileNorm, '/tests/')) {
            continue;
        }
        if (str_contains($otherFile, '/Shared/')) {
            // el bridge/abstract del propio proyecto no cuenta como caller
            if (str_contains($content, 'DomainEventBridgeSubscriber')
                || str_contains($content, 'DomainEventMessage')
                || str_contains($content, 'DomainEventPublisherInterface')
                || str_contains($content, 'AbstractDomainEvent')) {
                continue;
            }
        }
        if ($fqcn !== '' && str_contains($content, $fqcn)) {
            return true;
        }
        // nombre corto: solo cuenta si el FQCN es único en el proyecto (evita homónimos)
        if (($shortNameCount[$short] ?? 0) === 1 && preg_match('/\b'.preg_quote($short, '/').'\b/', $content)) {
            return true;
        }
    }

    return false;
}

/**
 * Los CLI Commands (Infrastructure/Console o Presentation/Console) se registran
 * con tag console.command en config/services.yaml — su caller es el framework,
 * no una referencia directa. Excepción documentada en specs/014-governance.
 */
function isCliCommand(string $file): bool
{
    $file = str_replace('\\', '/', $file);

    return str_contains($file, '/Infrastructure/Console/')
        || str_contains($file, '/Presentation/Console/');
}

/**
 * Subscribers que implementan EventSubscriberInterface se registran solos via
 * autoconfigure: true (config/services.yaml) — su caller es el framework.
 */
function isEventSubscriber(string $file, array $contents): bool
{
    $content = $contents[$file] ?? (string) file_get_contents($file);

    return str_contains($content, 'implements EventSubscriberInterface');
}

/**
 * @return list<string>
 */
function findFiles(string $dir, string $pattern): array
{
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
            $out[] = $file->getPathname();
        }
    }
    sort($out);

    return $out;
}
