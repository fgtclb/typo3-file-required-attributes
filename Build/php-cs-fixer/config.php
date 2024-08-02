<?php

/**
 *  $ php-cs-fixer fix --config ../.build/.php-cs-rules.php
 *
 * inside the TYPO3 directory. Warning: This may take up to 10 minutes.
 *
 * For more information read:
 *     https://www.php-fig.org/psr/psr-2/
 *     https://cs.sensiolabs.org
 */
/**
 * This file represents the configuration for Code Sniffing PSR12-related
 * automatic checks of coding guidelines.
 *
 * Run it using runTests.sh, see 'runTests.sh -h' for more options.
 *
 * Fix all local packages:
 * > Build/Scripts/runTests.sh -s cgl
 *
 * Fix your current patch:
 * > Build/Scripts/runTests.sh -s cglGit
 */
if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/');

return (new \PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP81Migration' => true,
        '@DoctrineAnnotation' => true,
        'no_leading_import_slash' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_unused_imports' => true,
        'concat_space' => ['spacing' => 'one'],
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'no_blank_lines_after_phpdoc' => true,
        'array_syntax' => ['syntax' => 'short'],
        'whitespace_after_comma_in_array' => true,
        'type_declaration_spaces' => true,
        'no_alias_functions' => true,
        'lowercase_cast' => true,
        'no_leading_namespace_whitespace' => true,
        'native_function_casing' => true,
        'no_short_bool_cast' => true,
        'no_unneeded_control_parentheses' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_trim' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'return_type_declaration' => ['space_before' => 'none'],
        'cast_spaces' => ['space' => 'none'],
        'dir_constant' => true,
        'phpdoc_no_access' => true,
        'no_multiple_statements_per_line' => true,
        'compact_nullable_type_declaration' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'modernize_types_casting' => true,
        'new_with_parentheses' => true,
        'no_empty_phpdoc' => true,
        'no_null_property_initialization' => true,
        'php_unit_mock_short_will_return' => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'single_trait_insert_per_statement' => true,
        'heredoc_indentation' => false,
        'single_space_around_construct' => true,
    ])
    ->setFinder($finder);
