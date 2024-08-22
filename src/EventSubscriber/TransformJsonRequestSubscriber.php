<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Makes the data of a JSON request body available as HttpFoundation\Request parameters
 *
 * Adapted from symfony-bundles/json-request-bundle
 */
class TransformJsonRequestSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [RequestEvent::class => ['transformJsonRequest', 2]];
    }

    public function transformJsonRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $content = $request->getContent();

        // @phpstan-ignore-next-line
        if (!is_string($content) || $content === '' || $request->getContentTypeFormat() !== 'json') {
            return;
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestException("Unable to parse JSON request data.");
        }

        if (!is_array($data)) {
            return;
        }

        $request->request->replace($data);
    }
}
