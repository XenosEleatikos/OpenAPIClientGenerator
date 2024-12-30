<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'no_unused_imports' => true,
    ]);
