<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in([__DIR__.'/src'])
    );
