<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Removes whitespace from both ends of strings in the request data
 *
 * Adapted from Laravel's Illuminate\Foundation\Http\Middleware\TrimStrings
 */
class TrimRequestDataSubscriber implements EventSubscriberInterface
{
    /**
     * The attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected array $except = [
        'password',
        'password_confirmation'
    ];

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [RequestEvent::class => 'trimRequestData'];
    }

    public function trimRequestData(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->clean($event->getRequest());
    }

    protected function clean(Request $request): void
    {
        $this->cleanParameterBag($request->query);

        if ($request->request === $request->query) {
            return;
        }

        $this->cleanParameterBag($request->request);
    }

    /**
     * @param ParameterBag<string, mixed> $bag
     */
    protected function cleanParameterBag(ParameterBag $bag): void
    {
        $bag->replace($this->cleanArray($bag->all()));
    }

    /**
     * @param array<mixed, mixed> $data
     * @return array<mixed, mixed>
     */
    protected function cleanArray(array $data, string $keyPrefix = ''): array
    {
        $keys = array_keys($data);

        $cleanedValues = array_map(
            function ($key, $value) use ($keyPrefix) {
                return $this->cleanValue($keyPrefix . $key, $value);
            },
            $keys,
            $data
        );

        $cleanedArray = array_combine($keys, $cleanedValues);

        if ($cleanedArray === false) {
            throw new \RuntimeException('Number of keys and values after cleanArray does not match.');
        }

        return $cleanedArray;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function cleanValue(string $key, $value)
    {
        if (is_array($value)) {
            return $this->cleanArray($value, $key . '.');
        }

        return $this->trim($key, $value);
    }

    /**
     * @param mixed $value
     * @return mixed string
     */
    protected function trim(string $key, $value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (in_array($key, $this->except, true)) {
            return $value;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
