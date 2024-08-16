<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends BaseController
{
    #[Route(path: '/', name: 'dashboard', options: ['expose' => true], methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderWithInertia('Dashboard/Index');
    }
}
