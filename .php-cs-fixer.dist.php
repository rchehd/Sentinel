<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/app/api/src')
;

$testsDir = __DIR__ . '/app/api/tests';
if (is_dir($testsDir)) {
    $finder->in($testsDir);
}

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
