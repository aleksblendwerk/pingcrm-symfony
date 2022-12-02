<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends BaseController
{
    #[Route(path: '/500', name: 'error_500')]
    public function internalServerError(): void
    {
        throw new HttpException(500);
    }

    #[Route(path: '/404', name: 'error_404')]
    public function pageNotFound(): void
    {
        throw new NotFoundHttpException();
    }
}
