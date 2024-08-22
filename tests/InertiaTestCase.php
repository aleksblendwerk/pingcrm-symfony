<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use App\Factory\Story\JohnFromAcmeStory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class InertiaTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var KernelBrowser $client */
        $client = $kernel->getContainer()->get('test.client'); // @phpstan-ignore-line
        $this->client = $client;

        /** @var User $user */
        $user = JohnFromAcmeStory::get('john')->_real();

        $this->client->loginUser($user);
        $this->client->followRedirects();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getPropsFromResponse(Response $response): array
    {
        $content = $response->getContent();

        if ($content === false) {
            throw new \RuntimeException('Unable to get response content.');
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \RuntimeException('Decoded response data is not an array.');
        }

        if (!array_key_exists('props', $data)) {
            throw new \RuntimeException('Key "props" not found in response data.');
        }

        if (!is_array($data['props'])) {
            throw new \RuntimeException('Value of "props" is not an array.');
        }

        return $data['props'];
    }
}
