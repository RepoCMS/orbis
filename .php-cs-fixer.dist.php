<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/config', __DIR__.'/migrations'])
    ->exclude('var')
    ->notName('Kernel.php')
    ->notName('bootstrap.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'blank_line_before_statement' => ['statements' => ['return']],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => false,
        'visibility_required' => true,
    ]);
