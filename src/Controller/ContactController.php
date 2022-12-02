<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\PaginationTrait;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use App\Util\RequestHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class ContactController extends BaseController
{
    use PaginationTrait;

    #[Route(
        path: '/contacts/{page}',
        name: 'contacts',
        requirements: ['page' => '\d+'],
        options: ['expose' => true],
        methods: ['GET']
    )]
    public function index(Request $request, ContactRepository $contactRepository, int $page = 1): Response
    {
        $search = RequestHelper::stringOrNull($request->query, 'search');
        $trashed = RequestHelper::stringOrNull($request->query, 'trashed');

        [$limit, $offset] = $this->getPaginationLimitAndOffset($page);

        $contacts = $this->wrapWithPaginationData(
            array_map(
                static function (Contact $contact): array {
                    return [
                        'id' => $contact->getId(),
                        'name' => $contact->getName(),
                        'phone' => $contact->getPhone(),
                        'city' => $contact->getCity(),
                        'deleted_at' => $contact->getDeletedAt(),
                        'organization' => $contact->getOrganization() !== null
                            ? ['name' => $contact->getOrganization()->getName()]
                            : null
                    ];
                },
                $contactRepository->findAllMatchingFilter(
                    $this->getCurrentUserAccount(),
                    $search,
                    $trashed,
                    $limit,
                    $offset
                )
            ),
            $contactRepository->countAllMatchingFilter($this->getCurrentUserAccount(), $search, $trashed),
            $page,
            'contacts'
        );

        return $this->renderWithInertia(
            'Contacts/Index',
            [
                'filters' => ['search' => $search, 'trashed' => $trashed],
                'contacts' => $contacts
            ]
        );
    }

    #[Route(
        path: '/contacts/create',
        name: 'contacts_create',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[Route(
        path: '/contacts/create',
        name: 'contacts_store',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function create(Request $request, OrganizationRepository $organizationRepository): Response
    {
        if ($request->getMethod() === 'POST') {
            $contact = new Contact();

            $contact->setAccount($this->getCurrentUserAccount());

            $errors = $this->handleFormData($request, $contact, 'Contact created.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('contacts'));
            }
        }

        $organizations = array_map(
            static function (Organization $organization): array {
                return [
                    'id' => $organization->getId(),
                    'name' => $organization->getName()
                ];
            },
            $organizationRepository->findAllByAccountOrderedByName($this->getCurrentUserAccount())
        );

        return $this->renderWithInertia(
            'Contacts/Create',
            [
                'errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject(),
                'organizations' => $organizations
            ]
        );
    }

    #[Route(
        path: '/contacts/{id}/edit',
        name: 'contacts_edit',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[Route(
        path: '/contacts/{id}/edit',
        name: 'contacts_update',
        options: ['expose' => true],
        methods: ['PUT']
    )]
    public function edit(Request $request, Contact $contact, OrganizationRepository $organizationRepository): Response
    {
        if ($request->getMethod() === 'PUT') {
            $errors = $this->handleFormData($request, $contact, 'Contact updated.');

            if (count($errors) === 0) {
                return new RedirectResponse($this->generateUrl('contacts_edit', ['id' => $contact->getId()]));
            }
        }

        $organizations = array_map(
            static function (Organization $organization): array {
                return [
                    'id' => $organization->getId(),
                    'name' => $organization->getName()
                ];
            },
            $organizationRepository->findAllByAccountOrderedByName($this->getCurrentUserAccount())
        );

        return $this->renderWithInertia('Contacts/Edit', [
            'contact' => [
                'id' => $contact->getId(),
                'first_name' => $contact->getFirstName(),
                'last_name' => $contact->getLastName(),
                'organization_id' => $contact->getOrganization() !== null ? $contact->getOrganization()->getId() : null,
                'email' => $contact->getEmail(),
                'phone' => $contact->getPhone(),
                'address' => $contact->getAddress(),
                'city' => $contact->getCity(),
                'region' => $contact->getRegion(),
                'country' => $contact->getCountry(),
                'postal_code' => $contact->getPostalCode(),
                'deleted_at' => $contact->getDeletedAt()
            ],
            'organizations' => $organizations,
            'errors' => isset($errors) ? new \ArrayObject($errors) : new \ArrayObject()
        ]);
    }

    /**
     * @return array<string, string> Validation errors
     */
    protected function handleFormData(Request $request, Contact $contact, string $successMessage): array
    {
        if (RequestHelper::stringOrNull($request->request, 'first_name') !== null) {
            $contact->setFirstName(RequestHelper::stringOrNull($request->request, 'first_name'));
        }

        if (RequestHelper::stringOrNull($request->request, 'last_name') !== null) {
            $contact->setLastName(RequestHelper::stringOrNull($request->request, 'last_name'));
        }

        $contact->setEmail(RequestHelper::stringOrNull($request->request, 'email'));
        $contact->setPhone(RequestHelper::stringOrNull($request->request, 'phone'));
        $contact->setAddress(RequestHelper::stringOrNull($request->request, 'address'));
        $contact->setCity(RequestHelper::stringOrNull($request->request, 'city'));
        $contact->setRegion(RequestHelper::stringOrNull($request->request, 'region'));
        $contact->setCountry(RequestHelper::stringOrNull($request->request, 'country'));
        $contact->setPostalCode(RequestHelper::stringOrNull($request->request, 'postal_code'));

        // TODO: this manual validation step could probably be done in a more elegant way...
        if (RequestHelper::intOrNull($request->request, 'organization_id') !== null) {
            $organizationId = $request->request->getInt('organization_id');
        } else {
            $organizationId = null;
        }

        $invalidOrganizationIdGiven = false;

        if ($organizationId !== null) {
            $organization = $this->getDoctrine()->getRepository(Organization::class)->find($organizationId);

            if ($organization !== null) {
                $contact->setOrganization($organization);
            } else {
                $invalidOrganizationIdGiven = true;
            }
        }

        $violations = $this->validator->validate($contact);

        if (!$invalidOrganizationIdGiven && $violations->count() === 0) {
            $this->getDoctrine()->getManager()->persist($contact);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', $successMessage);

            return [];
        }

        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $propertyName = (string) s($violation->getPropertyPath())->snake();

            $errors[$propertyName] = (string) $violation->getMessage();
        }

        if ($invalidOrganizationIdGiven) {
            $errors['organization_id'] = 'The given organization does not exist.';
        }

        return $errors;
    }

    #[Route(
        path: '/contacts/{id}/destroy',
        name: 'contacts_destroy',
        options: ['expose' => true],
        methods: ['DELETE']
    )]
    public function destroy(Contact $contact): Response
    {
        $this->getDoctrine()->getManager()->remove($contact);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Contact deleted.');

        return new RedirectResponse($this->generateUrl('contacts_edit', ['id' => $contact->getId()]));
    }

    #[Route(
        path: '/contacts/{id}/restore',
        name: 'contacts_restore',
        options: ['expose' => true],
        methods: ['PUT']
    )]
    public function restore(Contact $contact): Response
    {
        $contact->setDeletedAt(null);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Contact restored.');

        return new RedirectResponse($this->generateUrl('contacts_edit', ['id' => $contact->getId()]));
    }
}
