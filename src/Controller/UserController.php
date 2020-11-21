<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Util\ImageHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class UserController extends BaseController
{
    protected UserPasswordEncoderInterface $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @Route("/users/", name="users", methods={"GET"}, options={"expose"=true}))
     */
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->get('search');
        $role = $request->get('role');
        $trashed = $request->get('trashed');

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

    /**
     * @Route("/users/create", name="users_create", methods={"GET"}, options={"expose"=true}))
     * @Route("/users/store", name="users_store", methods={"POST"}, options={"expose"=true}))
     */
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

    /**
     * @Route("/users/{id}/edit", name="users_edit", methods={"GET"}, options={"expose"=true}))
     * @Route("/users/{id}/update", name="users_update", methods={"PUT"}, options={"expose"=true}))
     */
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
     * @return array<string, string> Validation errors
     */
    protected function handleFormData(Request $request, User $user, string $successMessage): array
    {
        $user->setFirstName($request->request->get('first_name'));
        $user->setLastName($request->request->get('last_name'));
        $user->setEmail($request->request->get('email'));
        $user->setOwner($request->request->getBoolean('owner'));

        if ($request->request->has('password')) {
            $password = $request->request->get('password');

            if ($password !== '' && $password !== null) {
                $user->setPassword($this->userPasswordEncoder->encodePassword($user, $password));
            }
        }

        if ($request->files->has('photo')) {
            $user->setPhotoFile($request->files->get('photo'));
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

            if ($propertyName === 'photo_filename') {
                $propertyName = 'photo';
            }

            $errors[$propertyName] = (string) $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @Route("/users/{id}/destroy", name="users_destroy", methods={"DELETE"}, options={"expose"=true}))
     */
    public function destroy(User $user): Response
    {
        $this->getDoctrine()->getManager()->remove($user);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'User deleted.');

        return new RedirectResponse($this->generateUrl('users_edit', ['id' => $user->getId()]));
    }

    /**
     * @Route("/users/{id}/restore", name="users_restore", methods={"PUT"}, options={"expose"=true}))
     */
    public function restore(User $user): Response
    {
        $user->setDeletedAt(null);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'User restored.');

        return new RedirectResponse($this->generateUrl('users_edit', ['id' => $user->getId()]));
    }
}
