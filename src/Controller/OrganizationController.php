<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\PaginationTrait;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use App\Util\RequestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class OrganizationController extends BaseController
{
    use PaginationTrait;

    public function __construct(
        RequestStack $requestStack,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($requestStack);
    }

    #[Route(
        path: '/organizations/{page}',
        name: 'organizations',
        requirements: ['page' => '\d+'],
        options: ['expose' => true],
        methods: ['GET']
    )]
    public function index(Request $request, OrganizationRepository $organizationRepository, int $page = 1): Response
    {
        $search = RequestHelper::stringOrNull($request->query, 'search');
        $trashed = RequestHelper::stringOrNull($request->query, 'trashed');

        [$limit, $offset] = $this->getPaginationLimitAndOffset($page);

        $organizations = $this->wrapWithPaginationData(
            array_map(
                static function (Organization $organization): array {
                    return [
                        'id' => $organization->getId(),
                        'name' => $organization->getName(),
                        'phone' => $organization->getPhone(),
                        'city' => $organization->getCity(),
                        'deleted_at' => $organization->getDeletedAt()
                    ];
                },
                $organizationRepository->findAllMatchingFilter(
                    $this->getCurrentUserAccount(),
                    $search,
                    $trashed,
                    $limit,
                    $offset
                )
            ),
            $organizationRepository->countAllMatchingFilter($this->getCurrentUserAccount(), $search, $trashed),
            $page,
            'organizations'
        );

        return $this->renderWithInertia(
            'Organizations/Index',
            [
                'filters' => ['search' => $search, 'trashed' => $trashed],
                'organizations' => $organizations
            ]
        );
    }

    #[Route(
        path: '/organizations/create',
        name: 'organizations_create',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[Route(
        path: '/organizations/create',
        name: 'organizations_store',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function create(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $organization = new Organization();

            $organization->setAccount($this->getCurrentUserAccount());

            $errors = $this->handleFormData($request, $organization, 'Organization created.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('organizations'));
            }
        }

        return $this->renderWithInertia(
            'Organizations/Create',
            ['errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject()]
        );
    }

    #[Route(
        path: '/organizations/{id}/edit',
        name: 'organizations_edit',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[Route(
        path: '/organizations/{id}/edit',
        name: 'organizations_update',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function edit(Request $request, Organization $organization): Response
    {
        if ($request->getMethod() === 'POST') {
            $errors = $this->handleFormData($request, $organization, 'Organization updated.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('organizations_edit', ['id' => $organization->getId()]));
            }
        }

        $contacts = array_map(
            static function (Contact $contact): array {
                return [
                    'id' => $contact->getId(),
                    'name' => $contact->getName(),
                    'city' => $contact->getCity(),
                    'phone' => $contact->getPhone()
                ];
            },
            $organization->getContacts()->toArray()
        );

        return $this->renderWithInertia('Organizations/Edit', [
            'organization' => [
                'id' => $organization->getId(),
                'name' => $organization->getName(),
                'email' => $organization->getEmail(),
                'phone' => $organization->getPhone(),
                'address' => $organization->getAddress(),
                'city' => $organization->getCity(),
                'region' => $organization->getRegion(),
                'country' => $organization->getCountry(),
                'postal_code' => $organization->getPostalCode(),
                'deleted_at' => $organization->getDeletedAt(),
                'contacts' => $contacts
            ],
            'errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject()
        ]);
    }

    /**
     * @return array<string, string> Validation errors
     */
    protected function handleFormData(Request $request, Organization $organization, string $successMessage): array
    {
        $organization->setName(RequestHelper::stringOrNull($request->request, 'name'));
        $organization->setEmail(RequestHelper::stringOrNull($request->request, 'email'));
        $organization->setPhone(RequestHelper::stringOrNull($request->request, 'phone'));
        $organization->setAddress(RequestHelper::stringOrNull($request->request, 'address'));
        $organization->setCity(RequestHelper::stringOrNull($request->request, 'city'));
        $organization->setRegion(RequestHelper::stringOrNull($request->request, 'region'));
        $organization->setCountry(RequestHelper::stringOrNull($request->request, 'country'));
        $organization->setPostalCode(RequestHelper::stringOrNull($request->request, 'postal_code'));

        $violations = $this->validator->validate($organization);

        if ($violations->count() === 0) {
            $this->entityManager->persist($organization);
            $this->entityManager->flush();

            $this->addFlash('success', $successMessage);

            return [];
        }

        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $propertyName = (string) s($violation->getPropertyPath())->snake();

            $errors[$propertyName] = (string) $violation->getMessage();
        }

        return $errors;
    }

    #[Route(
        path: '/organizations/{id}/destroy',
        name: 'organizations_destroy',
        options: ['expose' => true],
        methods: ['DELETE']
    )]
    public function destroy(Organization $organization): Response
    {
        $this->entityManager->remove($organization);
        $this->entityManager->flush();
        $this->addFlash('success', 'Contact deleted.');

        return new RedirectResponse($this->generateUrl('organizations_edit', ['id' => $organization->getId()]));
    }

    #[Route(
        path: '/organizations/{id}/restore',
        name: 'organizations_restore',
        options: ['expose' => true],
        methods: ['PUT']
    )]
    public function restore(Organization $organization): Response
    {
        $organization->setDeletedAt(null);
        $this->entityManager->flush();
        $this->addFlash('success', 'Organization restored.');

        return new RedirectResponse($this->generateUrl('organizations_edit', ['id' => $organization->getId()]));
    }
}
