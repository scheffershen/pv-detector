<?php

// old
// $finder = (new PhpCsFixer\Finder())
//     ->in(__DIR__)
//     ->exclude('var')
// ;

// return (new PhpCsFixer\Config())
//     ->setRules([
//         '@Symfony' => true,
//     ])
//     ->setFinder($finder)
//     ->setCacheFile('.php-cs-fixer.cache') // forward compatibility with 3.x line
// ;

$rules = [
    '@Symfony' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
    'phpdoc_to_comment' => false,
    'phpdoc_align' => false,
    'php_unit_method_casing' => false,
];

$finder = PhpCsFixer\Finder::create()
    ->in([
    __DIR__.'/src',
    __DIR__.'/tests',
]);

$config = new PhpCsFixer\Config();
return $config
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
;