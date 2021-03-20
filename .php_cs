<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => ['statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try']],
        'class_attributes_separation' => ['elements' => ['const', 'method', 'property']],
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra']],
        'no_leading_namespace_whitespace' => true,
        'no_unused_imports' => true,
        'no_useless_return' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'ordered_class_elements' => ['order' => ['use_trait', 'constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'construct', 'destruct', 'magic', 'phpunit', 'method_public', 'method_protected', 'method_private'], 'sortAlgorithm' => 'none'],
        'phpdoc_no_useless_inheritdoc' => true,
        'single_blank_line_at_eof' => true,
        'single_class_element_per_statement' => ['elements' => ['const', 'property']],
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'visibility_required' => ['elements' => ['property', 'method']],
        'phpdoc_line_span' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true
        ],
    ])
    ->setFinder($finder)
;
