<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PSR2' => true,
    '@PSR12' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'binary_operator_spaces' => true,
    'blank_line_after_namespace' => true,
    'blank_line_before_statement' => true,
    'cast_spaces' => true,
    'concat_space' => [
        'spacing' => 'none',
    ],
    'ereg_to_preg' => true,
    'is_null' => true,
    'line_ending' => true,
    'modernize_types_casting' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_short_bool_cast' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unused_imports' => true,
    'no_whitespace_in_blank_line' => true,
    'ordered_imports' => true,
    'phpdoc_align' => false,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_order' => true,
    'phpdoc_scalar' => true,
    'phpdoc_separation' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_var_without_name' => true,
    'self_accessor' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline' => true,
    'trim_array_spaces' => true,
    'yoda_style' => [
        'always_move_variable' => false,
        'equal' => false,
        'identical' => false,
    ],
];

$finder = Finder::create()->in(__DIR__)
    ->notPath([
        'src/ShopifyApp/resources/config/shopify-app.php',
    ]);

return (new Config())
    ->setRules($rules)
    ->setFinder($finder)
    ->setRiskyAllowed(true);
