<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends BaseController
{
    /**
     * @Route("/", name="dashboard", methods={"GET"}, options={"expose"=true})
     */
    public function index(): Response
    {
        return $this->renderWithInertia('Dashboard/Index');
    }
}
