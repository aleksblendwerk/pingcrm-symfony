<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\PaginationTrait;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;

use function Symfony\Component\String\s;

class OrganizationController extends BaseController
{
    use PaginationTrait;

    /**
     * @Route(
     *     "/organizations/{page}",
     *     name="organizations",
     *     requirements={"page"="\d+"},
     *     methods={"GET"},
     *     options={"expose"=true})
     * )
     */
    public function index(Request $request, OrganizationRepository $organizationRepository, int $page = 1): Response
    {
        $search = $request->get('search');
        $trashed = $request->get('trashed');

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

    /**
     * @Route("/organizations/create", name="organizations_create", methods={"GET"}, options={"expose"=true}))
     * @Route("/organizations/create", name="organizations_store", methods={"POST"}, options={"expose"=true}))
     */
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

    /**
     * @Route("/organizations/{id}/edit", name="organizations_edit", methods={"GET"}, options={"expose"=true}))
     * @Route("/organizations/{id}/edit", name="organizations_update", methods={"PUT"}, options={"expose"=true}))
     */
    public function edit(Request $request, Organization $organization): Response
    {
        if ($request->getMethod() === 'PUT') {
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
        $organization->setName($request->request->get('name'));
        $organization->setEmail($request->request->get('email'));
        $organization->setPhone($request->request->get('phone'));
        $organization->setAddress($request->request->get('address'));
        $organization->setCity($request->request->get('city'));
        $organization->setRegion($request->request->get('region'));
        $organization->setCountry($request->request->get('country'));
        $organization->setPostalCode($request->request->get('postal_code'));

        $violations = $this->validator->validate($organization);

        if ($violations->count() === 0) {
            $this->getDoctrine()->getManager()->persist($organization);
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

        return $errors;
    }

    /**
     * @Route("/organizations/{id}/destroy", name="organizations_destroy", methods={"DELETE"}, options={"expose"=true}))
     */
    public function destroy(Organization $organization): Response
    {
        $this->getDoctrine()->getManager()->remove($organization);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Contact deleted.');

        return new RedirectResponse($this->generateUrl('organizations_edit', ['id' => $organization->getId()]));
    }

    /**
     * @Route("/organizations/{id}/restore", name="organizations_restore", methods={"PUT"}, options={"expose"=true}))
     */
    public function restore(Organization $organization): Response
    {
        $organization->setDeletedAt(null);
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'Organization restored.');

        return new RedirectResponse($this->generateUrl('organizations_edit', ['id' => $organization->getId()]));
    }
}
