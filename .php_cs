<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in('src')
    ->in('tests');

return Symfony\CS\Config\Config::create()
    ->level(\Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(array(
        'duplicate_semicolon',
        'extra_empty_lines',
        'multiline_array_trailing_comma',
        'namespace_no_leading_whitespace',
        'new_with_braces',
        'object_operator',
        'operators_spaces',
        'phpdoc_params',
        'remove_lines_between_uses',
        'single_array_no_trailing_comma',
        'spaces_before_semicolon',
        'ternary_spaces',
        'unused_use',
        'concat_with_spaces',
        'ordered_use',
        'single_blank_line_before_namespace',
        'remove_leading_slash_use',
    ))
    ->finder($finder);
