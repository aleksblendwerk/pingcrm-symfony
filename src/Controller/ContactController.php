<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\PaginationTrait;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class ContactController extends BaseController
{
    use PaginationTrait;

    /**
     * @Route(
     *     "/contacts/{page}",
     *     name="contacts",
     *     requirements={"page"="\d+"},
     *     methods={"GET"},
     *     options={"expose"=true})
     * )
     */
    public function index(Request $request, ContactRepository $contactRepository, int $page = 1): Response
    {
        $search = $request->get('search');
        $trashed = $request->get('trashed');

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

    /**
     * @Route("/contacts/create", name="contacts_create", methods={"GET"}, options={"expose"=true}))
     * @Route("/contacts/create", name="contacts_store", methods={"POST"}, options={"expose"=true}))
     */
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

    /**
     * @Route("/contacts/{id}/edit", name="contacts_edit", methods={"GET"}, options={"expose"=true}))
     * @Route("/contacts/{id}/edit", name="contacts_update", methods={"PUT"}, options={"expose"=true}))
     */
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
        $contact->setFirstName($request->request->get('first_name'));
        $contact->setLastName($request->request->get('last_name'));
        $contact->setEmail($request->request->get('email'));
        $contact->setPhone($request->request->get('phone'));
        $contact->setAddress($request->request->get('address'));
        $contact->setCity($request->request->get('city'));
        $contact->setRegion($request->request->get('region'));
        $contact->setCountry($request->request->get('country'));
        $contact->setPostalCode($request->request->get('postal_code'));

        // TODO: this manual validation step could probably be done in a more elegant way...
        $organizationId = $request->request->get('organization_id');

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

    /**
     * @Route("/contacts/{id}/destroy", name="contacts_destroy", methods={"DELETE"}, options={"expose"=true}))
     */
    public function destroy(Contact $contact): Response
    {
        $this->getDoctrine()->getManager()->remove($contact);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Contact deleted.');

        return new RedirectResponse($this->generateUrl('contacts_edit', ['id' => $contact->getId()]));
    }

    /**
     * @Route("/contacts/{id}/restore", name="contacts_restore", methods={"PUT"}, options={"expose"=true}))
     */
    public function restore(Contact $contact): Response
    {
        $contact->setDeletedAt(null);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Contact restored.');

        return new RedirectResponse($this->generateUrl('contacts_edit', ['id' => $contact->getId()]));
    }
}
