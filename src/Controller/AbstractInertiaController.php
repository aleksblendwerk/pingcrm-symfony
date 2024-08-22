<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\BuildInertiaDefaultPropsTrait;
use App\Entity\User;
use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractInertiaController extends AbstractController
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected ValidatorInterface $validator;

    public function __construct(protected RequestStack $requestStack) {}

    #[Required]
    public function setInertia(InertiaInterface $inertia): void
    {
        $this->inertia = $inertia;
    }

    #[Required]
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

        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \RuntimeException('There is no current request.');
        }

        $defaultProps = $this->buildDefaultProps($request, $currentUser);

        return $this->inertia->render($component, array_merge($defaultProps, $props), $viewData, $context);
    }
}
