<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Util\ImageHandler;
use App\Util\RequestHelper;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class UserController extends BaseController
{
    public function __construct(
        RequestStack $requestStack,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct($requestStack);
    }

    #[Route(path: '/users/', name: 'users', options: ['expose' => true], methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = RequestHelper::stringOrNull($request->query, 'search');
        $role = RequestHelper::stringOrNull($request->query, 'role');
        $trashed = RequestHelper::stringOrNull($request->query, 'trashed');

        $users = array_map(
            static function (User $user): array {
                return [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'owner' => $user->isOwner(),
                    'photo' => $user->getPhotoFilename() !== null
                        ? ImageHandler::resolvePathToResizedImage('/images/' . $user->getPhotoFilename(), 40, 40)
                        : null,
                    'deleted_at' => $user->getDeletedAt()
                ];
            },
            $userRepository->findAllMatchingFilter($this->getCurrentUserAccount(), $search, $role, $trashed)
        );

        return $this->renderWithInertia(
            'Users/Index',
            [
                'filters' => ['search' => $search, 'role' => $role, 'trashed' => $trashed],
                'users' => $users
            ]
        );
    }

    #[Route(path: '/users/create', name: 'users_create', options: ['expose' => true], methods: ['GET'])]
    #[Route(path: '/users/create', name: 'users_store', options: ['expose' => true], methods: ['POST'])]
    public function create(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $user = new User();

            $user->setAccount($this->getCurrentUserAccount());

            $errors = $this->handleFormData($request, $user, 'User created.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('users'));
            }
        }

        return $this->renderWithInertia(
            'Users/Create',
            ['errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject()]
        );
    }

    #[Route(path: '/users/{id}/edit', name: 'users_edit', options: ['expose' => true], methods: ['GET'])]
    #[Route(path: '/users/{id}/edit', name: 'users_update', options: ['expose' => true], methods: ['PUT'])]
    public function edit(Request $request, User $user): Response
    {
        if ($request->getMethod() === 'PUT') {
            $errors = $this->handleFormData($request, $user, 'User updated.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('users_edit', ['id' => $user->getId()]));
            }
        }

        $userData = [
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'owner' => $user->isOwner(),
            'photo' => $user->getPhotoFilename() !== null
                ? ImageHandler::resolvePathToResizedImage('/images/' . $user->getPhotoFilename(), 60, 60)
                : null,
            'deleted_at' => $user->getDeletedAt()
        ];

        /*
         * edge case: when editing the currently logged in user and there were errors,
         * we need to assure we reload a valid state here, otherwise this will mess up the authentication system
         */
        if ($request->getMethod() === 'PUT' && $user === $this->getUser()) {
            $this->getDoctrine()->getManager()->refresh($user);
        }

        return $this->renderWithInertia('Users/Edit', [
            'user' => $userData,
            'errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject()
        ]);
    }

    /**
     * @return array<string, mixed> Validation errors
     */
    protected function handleFormData(Request $request, User $user, string $successMessage): array
    {
        $user->setFirstName(RequestHelper::stringOrNull($request->request, 'first_name'));
        $user->setLastName(RequestHelper::stringOrNull($request->request, 'last_name'));
        $user->setEmail(RequestHelper::stringOrNull($request->request, 'email'));
        $user->setOwner($request->request->getBoolean('owner'));

        if (RequestHelper::stringOrNull($request->request, 'password') !== null) {
            $password = RequestHelper::stringOrNull($request->request, 'password');

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
        }

        if ($request->files->has('photo')) {
            $photo = $request->files->get('photo');

            if ($photo instanceof File) {
                $user->setPhotoFile($photo);
            } elseif ($photo === null) {
                $user->setPhotoFile(null);
            }
        }

        $violations = $this->validator->validate($user);

        if ($violations->count() === 0) {
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', $successMessage);

            return [];
        }

        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $propertyName = (string) s($violation->getPropertyPath())->snake();

            if ($propertyName === 'photo_file') {
                if (!array_key_exists('photo', $errors)) {
                    $errors['photo'] = [];
                }

                // @phpstan-ignore-next-line
                $errors['photo'][] = (string) $violation->getMessage();

                continue;
            }

            $errors[$propertyName] = (string) $violation->getMessage();
        }

        return $errors;
    }

    #[Route(path: '/users/{id}/destroy', name: 'users_destroy', options: ['expose' => true], methods: ['DELETE'])]
    public function destroy(User $user): Response
    {
        $this->getDoctrine()->getManager()->remove($user);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'User deleted.');

        return new RedirectResponse($this->generateUrl('users_edit', ['id' => $user->getId()]));
    }

    #[Route(path: '/users/{id}/restore', name: 'users_restore', options: ['expose' => true], methods: ['PUT'])]
    public function restore(User $user): Response
    {
        $user->setDeletedAt(null);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'User restored.');

        return new RedirectResponse($this->generateUrl('users_edit', ['id' => $user->getId()]));
    }
}
