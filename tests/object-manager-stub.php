<?php

declare(strict_types=1);

use App\Kernel;

require 'bootstrap.php';

// @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// provides the project's doctrine object manager, required by phpstan/phpstan-doctrine
return $kernel->getContainer()->get('doctrine')->getManager();
