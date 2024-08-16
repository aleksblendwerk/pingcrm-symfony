<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends BaseController
{
    #[Route(path: '/reports/', name: 'reports', options: ['expose' => true], methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderWithInertia('Reports/Index');
    }
}
