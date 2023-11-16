<?php

$rules = [
    '@Symfony' => true,
    '@DoctrineAnnotation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'echo_tag_syntax' => ['format' => 'long'],
    'no_unused_imports' => true,
    'ordered_class_elements' => true,
    'ordered_imports' => true,
    'phpdoc_order' => true,
];

$finder = PhpCsFixer\Finder::create()
    ->exclude(['etc', 'vendor'])
    ->notPath('#(^|/)_.+(/|$)#')
    ->in(__DIR__);

$cacheDir = getenv('TRAVIS')
    ? getenv('HOME').'/.php-cs-fixer'
    : __DIR__;

return (new PhpCsFixer\Config())
    ->setCacheFile($cacheDir.'/.php-cs-fixer.cache')
    ->setRules($rules)
    ->setFinder($finder);
