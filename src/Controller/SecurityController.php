<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends BaseController
{
    #[Route(path: '/login', name: 'login', options: ['expose' => true], methods: ['GET'])]
    #[Route(path: '/login', name: 'login_attempt', options: ['expose' => true], methods: ['POST'])]
    public function login(): Response
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
