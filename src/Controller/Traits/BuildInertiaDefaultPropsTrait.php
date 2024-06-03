<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

trait BuildInertiaDefaultPropsTrait
{
    /**
     * @return array<string, mixed>
     */
    protected function buildDefaultProps(Request $request, ?User $user): array
    {
        $flashSuccessMessage = null;
        $flashErrorMessage = null;

        // @phpstan-ignore-next-line
        if ($request->hasSession()) {
            /** @var Session $session */
            $session = $request->getSession();

            if ($session->getFlashBag()->has('success')) {
                $flashSuccessMessages = $session->getFlashBag()->get('success');
                $flashSuccessMessage = reset($flashSuccessMessages);
            }

            if ($session->getFlashBag()->has('error')) {
                $flashErrorMessages = $session->getFlashBag()->get('error');
                $flashErrorMessage = reset($flashErrorMessages);
            }
        }

        if ($user !== null && $user->getAccount() !== null) {
            $account = ['id' => $user->getAccount()->getId(), 'name' => $user->getAccount()->getName()];
        } else {
            $account = [];
        }

        return [
            'errors' => new \ArrayObject(),
            'auth' => [
                'user' => $user !== null
                    ? [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'account' => $account,
                        'role' => null // TODO: not sure what the vue app expects here yet...
                    ]
                    : null
            ],
            'flash' => [
                'success' => $flashSuccessMessage,
                'error' => $flashErrorMessage
            ]
        ];
    }
}
