<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\BuildInertiaDefaultPropsTrait;
use App\Entity\User;
use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractInertiaController extends AbstractController
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected ValidatorInterface $validator;

    /**
     * @required
     */
    public function setInertia(InertiaInterface $inertia): void
    {
        $this->inertia = $inertia;
    }

    /**
     * @required
     */
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $viewData
     * @param array<string, mixed> $context
     */
    protected function renderWithInertia(
        string $component,
        array $props = [],
        array $viewData = [],
        array $context = []
    ): Response {
        /** @var ?User $currentUser */
        $currentUser = $this->getUser();

        $request = $this->get('request_stack')->getCurrentRequest();

        if ($request === null) {
            throw new \RuntimeException('There is no current request.');
        }

        $defaultProps = $this->buildDefaultProps($request, $currentUser);

        return $this->inertia->render($component, array_merge($defaultProps, $props), $viewData, $context);
    }
}
