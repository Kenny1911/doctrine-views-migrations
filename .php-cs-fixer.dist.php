<?php

declare(strict_types=1);

use Kenny1911\ClassVisibilityFixer\ClassVisibilityFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$config = (new Config())
    ->setFinder(
        Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->append([
                __FILE__,
            ]),
    )
    ->registerCustomFixers([
        new ClassVisibilityFixer(),
    ])
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__ . '/var/' . basename(__FILE__) . '.cache');

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'yoda_style' => true,
    'Kenny1911/class_visibility' => [
        'defaultVisibility' => 'api',
    ],
]);

return $config;
