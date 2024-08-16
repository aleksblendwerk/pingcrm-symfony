<?php

declare(strict_types=1);

namespace App\Security;

use App\Controller\Traits\BuildInertiaDefaultPropsTrait;
use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected RouterInterface $router;

    public function __construct(InertiaInterface $inertia, RouterInterface $router)
    {
        $this->inertia = $inertia;
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $props = $this->buildDefaultProps($request, null);

        $props['errors'] = ['email' => $exception->getMessage()];

        return $this->inertia->render('Auth/Login', $props);
    }
}
