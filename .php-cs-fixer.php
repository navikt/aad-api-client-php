<?php declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use NAVIT\CodingStandard\Config;
use Symfony\Component\Finder\Finder;

$finder = (new Finder())
    ->files()
    ->name('*.php')
    ->in(__DIR__)
    ->exclude('vendor');

return (new Config())
    ->setFinder($finder);
