<?php

declare(strict_types=1);

use App\Factory\Story\JohnFromAcmeStory;
use Symfony\Component\Dotenv\Dotenv;
use Zenstruck\Foundry\Test\TestState;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

TestState::addGlobalState(static function (): void {
    JohnFromAcmeStory::load();
});
