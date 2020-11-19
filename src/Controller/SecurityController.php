<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    /**
     * @Route("/login", name="login", methods={"GET"}, options={"expose"=true}))
     * @Route("/login", name="login_attempt", methods={"POST"}, options={"expose"=true}))
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        /** @var ?User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser !== null) {
            if ($currentUser->getAccount() === null) {
                throw new \RuntimeException('The current user is not associated with a valid account.');
            }

            return $this->redirectToRoute('dashboard');
        }

        return $this->renderWithInertia('Auth/Login');
    }
}
