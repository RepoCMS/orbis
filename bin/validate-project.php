<?php

declare(strict_types=1);

/**
 * Validador unificado del proyecto (reglas Apéndice A automatizables + estructura + secrets).
 *
 * Es el gate `validate` del CI (specs/014-governance §5-6). Cross-platform:
 * Linux (CI), Windows y Mac.
 *
 * Uso: php bin/validate-project.php
 * Exit 0 = OK · Exit 1 = errores (bloquean) · Exit 2 = warnings (no bloquean).
 */

$root = dirname(__DIR__);
$errors = [];
$warnings = [];
$ok = 0;

/** Baseline de reglas: errores preexistentes permitidos (ver validate-baseline.txt). */
$baseline = [];
if (file_exists("$root/validate-baseline.txt")) {
    foreach (file("$root/validate-baseline.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line !== '' && !str_starts_with($line, '#')) {
            $baseline[$line] = true;
        }
    }
}

function isBaselined(string $key): bool
{
    global $baseline;

    return isset($baseline[$key]);
}

function ok(string $msg): void
{
    global $ok;
    ++$ok;
    fwrite(STDOUT, "  [OK] $msg\n");
}

function fail(string $msg, bool $blocking = true): void
{
    global $errors, $warnings;
    if ($blocking) {
        $errors[] = $msg;
        fwrite(STDOUT, "  [ERROR] $msg\n");
    } else {
        $warnings[] = $msg;
        fwrite(STDOUT, "  [WARN] $msg\n");
    }
}

fwrite(STDOUT, "VALIDANDO PROYECTO ORBIS\n");
fwrite(STDOUT, "=======================\n\n");

// ── 1. Archivos esenciales ────────────────────────────────────────────────
fwrite(STDOUT, "1. Archivos esenciales\n");
$required = ['CONTEXT.md', 'tasks/TODO.md', 'tasks/IN_PROGRESS.md', 'tasks/DONE.md', 'AGENTS.md', 'HANDOFF.md', 'composer.json', 'phpstan.neon', 'phpstan-baseline.neon', '.php-cs-fixer.dist.php'];
foreach ($required as $f) {
    file_exists("$root/$f") ? ok($f) : fail("$f faltante");
}

// ── 2. Directorios críticos ───────────────────────────────────────────────
fwrite(STDOUT, "\n2. Directorios criticos\n");
foreach (['src', 'specs', 'tests', 'config', 'templates', 'migrations', 'assets', 'bin'] as $d) {
    is_dir("$root/$d") ? ok("$d/") : fail("$d/ no existe", false);
}

// ── 3. Artefactos no deseados en repo (trackeados) ───────────────────────
fwrite(STDOUT, "\n3. Artefactos no deseados en git\n");
$tracked = [];
exec('git ls-files', $tracked, $gitExit);
if ($gitExit === 0) {
    foreach ($tracked as $file) {
        if (preg_match('#^\.env(\.local|\.dev|\.prod|\.test)?$#', $file)
            || str_ends_with($file, '.key') && str_contains($file, 'jwt')
            || str_ends_with($file, '.pem')) {
            fail("secret trackeado en git: $file");
        }
    }
    if (preg_grep('#^\.env(\.|$)#', $tracked) === []) {
        ok('.env reales no trackeados');
    }
} else {
    fail('git no disponible (no se puede validar secrets trackeados)', false);
}

// ── 4. Regla 1: declare(strict_types=1) en TODO archivo PHP ──────────────
fwrite(STDOUT, "\n4. Regla 1 (Apéndice A): declare(strict_types=1)\n");
$noStrict = [];
foreach (phpFiles("$root/src") as $file) {
    $content = (string) file_get_contents($file);
    if (!preg_match('/^declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', $content)) {
        $noStrict[] = rel($file);
    }
}
if ($noStrict === []) {
    ok('strict_types=1 en todo src/');
} else {
    fail(sprintf('sin strict_types (%d): %s', count($noStrict), implode(', ', array_slice($noStrict, 0, 10))));
}

// ── 5. Regla 2: enum values lowercase snake_case ─────────────────────────
fwrite(STDOUT, "\n5. Regla 2 (Apéndice A): enum values lowercase\n");
$badEnums = [];
foreach (phpFiles("$root/src") as $file) {
    $content = (string) file_get_contents($file);
    if (!preg_match('/\benum\s+\w+\s*:\s*string\b/', $content)) {
        continue;
    }
    if (preg_match_all('/\bcase\s+\w+\s*=\s*\x27([^\x27]+)\x27\s*;/', $content, $m)) {
        foreach ($m[1] as $value) {
            if ($value !== strtolower($value) || preg_match('/[^a-z0-9_]/', $value)) {
                $badEnums[rel($file)] = true;
            }
        }
    }
}
if ($badEnums === []) {
    ok('enum values lowercase');
} else {
    foreach ($badEnums as $file => $_) {
        if (isBaselined("regla2|$file")) {
            $warnings[] = "regla2|$file (baseline)";
            fwrite(STDOUT, "  [WARN] enum values UPPERCASE (baseline): $file\n");
        } else {
            $errors[] = "regla2|$file";
            fwrite(STDOUT, "  [ERROR] enum values invalidos: $file\n");
        }
    }
}

// ── 6. Regla 6: Commands/Queries final readonly class ────────────────────
fwrite(STDOUT, "\n6. Regla 6 (Apéndice A): Commands/Queries final readonly\n");
$badCQ = [];
foreach (phpFiles("$root/src") as $file) {
    if (!preg_match('#/Application/(Command|Query)/#', str_replace('\\', '/', $file))) {
        continue;
    }
    $name = basename($file);
    if (!str_ends_with($name, 'Command.php') && !str_ends_with($name, 'Query.php')) {
        continue;
    }
    $content = (string) file_get_contents($file);
    if (!preg_match('/\bfinal\s+readonly\s+class\s+\w+/', $content)) {
        $badCQ[] = rel($file);
    }
}
if ($badCQ === []) {
    ok('Commands/Queries final readonly');
} else {
    fail(sprintf('sin final readonly (%d): %s', count($badCQ), implode(', ', array_slice($badCQ, 0, 10))));
}

// ── 7. Regla 9: sufijo ValueObject ───────────────────────────────────────
fwrite(STDOUT, "\n7. Regla 9 (Apéndice A): sufijo ValueObject\n");
$badVo = [];
foreach (phpFiles("$root/src") as $file) {
    $norm = str_replace('\\', '/', $file);
    if (!preg_match('#/Domain/(ValueObject|ValueObjects|VO)s?/#', $norm)) {
        continue;
    }
    $content = (string) file_get_contents($file);
    if (preg_match('/\b(class|final\s+class|final\s+readonly\s+class)\s+([A-Za-z0-9_]+)/', $content, $m)
        && !str_ends_with($m[2], 'ValueObject')
        && !str_ends_with($m[2], 'Exception')) {
        $badVo[rel($file)] = true;
    }
}
if ($badVo === []) {
    ok('VOs con sufijo ValueObject');
} else {
    foreach ($badVo as $file => $_) {
        if (isBaselined("regla9|$file")) {
            $warnings[] = "regla9|$file (baseline)";
            fwrite(STDOUT, "  [WARN] VO sin sufijo (baseline): $file\n");
        } else {
            $errors[] = "regla9|$file";
            fwrite(STDOUT, "  [ERROR] VO sin sufijo: $file\n");
        }
    }
}

// ── 8. Regla 11: wiring (delega en bin/wiring-check.php) ─────────────────
fwrite(STDOUT, "\n8. Regla 11 (Apéndice A): wiring (commands/queries/events con caller)\n");
$wiringOut = [];
exec(sprintf('"%s" bin/wiring-check.php 2>&1', PHP_BINARY), $wiringOut, $wiringExit);
if ($wiringExit === 0) {
    ok(trim((string) end($wiringOut)));
} else {
    fail('wiring check fallo (bin/wiring-check.php) — revisar comandos/queries/eventos huerfanos');
}

// ── 9. Secrets en código ─────────────────────────────────────────────────
fwrite(STDOUT, "\n9. Secrets en codigo\n");
$secretPatterns = [
    '/sk-[A-Za-z0-9]{20,}/',                    // OpenAI API keys
    '/AKIA[0-9A-Z]{16}/',                       // AWS keys
    '/-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    '/password\s*[:=]\s*[\x27"][^\x27"]{4,}[\x27"]/i',
    '/mysql:\/\/[^:\/]+:[^@]+@/',               // DSN con credenciales
    '/OPENROUTER_API_KEY\s*=\s*[^\s$]+/',       // token embebido (no variable)
];
$secretFiles = [];
foreach (phpFiles("$root/src") as $file) {
    $content = (string) file_get_contents($file);
    foreach ($secretPatterns as $p) {
        if (preg_match($p, $content) && !str_contains($content, 'LOGIN_FAILURE')) {
            $secretFiles[] = rel($file);
            break;
        }
    }
}
if ($secretFiles === []) {
    ok('sin secrets en src/');
} else {
    fail(sprintf('posibles secrets (%d): %s', count($secretFiles), implode(', ', array_slice($secretFiles, 0, 10))));
}

// ── 10. Duplicados globales (regla: config global vs repo) ───────────────
fwrite(STDOUT, "\n10. Regla global vs repo: sin agentes/skills duplicados\n");
$forbidden = ['.opencode/agent', '.claude/skills', '.claude/agents', '.codex/skills', '.agents'];
foreach ($forbidden as $dup) {
    if (is_dir("$root/$dup")) {
        fail("duplicado global en repo: $dup (fuente unica: ~/.agents/) — borrar");
    }
}
if (count(array_filter($forbidden, fn ($d) => is_dir("$root/$d"))) === 0) {
    ok('sin agentes/skills duplicados en repo');
}

// ── 11. Tablero sincronizado (básico) ────────────────────────────────────
fwrite(STDOUT, "\n11. Tablero de tareas\n");
$todo = (string) file_get_contents("$root/tasks/TODO.md");
if (preg_match('/\[ \]/', $todo)) {
    ok('tasks/TODO.md con backlog activo');
} else {
    fail('tasks/TODO.md sin tareas pendientes — revisar si el backlog esta vacio o el formato cambio', false);
}

// ── Resumen ──────────────────────────────────────────────────────────────
fwrite(STDOUT, "\n========================================\n");
fwrite(STDOUT, sprintf("OK: %d · Errores: %d · Warnings: %d\n", $ok, count($errors), count($warnings)));
if ($errors !== []) {
    fwrite(STDERR, "VALIDACION FALLIDA — errores bloqueantes.\n");
    exit(1);
}
if ($warnings !== []) {
    fwrite(STDOUT, "Validacion con warnings (no bloquean).\n");
    exit(2);
}
fwrite(STDOUT, "OK — Validacion completada.\n");
exit(0);

/**
 * @return list<string>
 */
function phpFiles(string $dir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            $out[] = $file->getPathname();
        }
    }
    sort($out);

    return $out;
}

function rel(string $path): string
{
    global $root;

    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}
