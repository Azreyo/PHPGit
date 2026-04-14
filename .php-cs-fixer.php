<?php

declare(strict_types=1);

/**
 * PHP CS Fixer configuration for PHPGit.
 *
 * Enforces PSR-12 with a few project-specific overrides.
 * Run locally:  vendor/bin/php-cs-fixer fix --dry-run --diff
 * Auto-fix:     vendor/bin/php-cs-fixer fix
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'normalize_index_brace' => true,
        'single_quote' => true,
        'explicit_string_variable' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_successor_space' => true,
        'lambda_not_used_import' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'yoda_style' => false,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block', 'extra', 'parenthesis_brace_block',
                'square_brace_block', 'throw', 'use',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],
        'cast_spaces' => ['space' => 'single'],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'no_empty_phpdoc' => true,
    ])
    ->setFinder($finder);