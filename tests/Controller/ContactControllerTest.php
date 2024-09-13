<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Entity\Organization;
use App\Factory\ContactFactory;
use App\Factory\OrganizationFactory;
use App\Factory\Story\JohnFromAcmeStory;
use App\Tests\InertiaTestCase;

class ContactControllerTest extends InertiaTestCase
{
    public function testCanViewContacts(): void
    {
        $a = JohnFromAcmeStory::get('acme');

        ContactFactory::createMany(5, ['account' => $a]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/contacts',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(5, $props['contacts']['data']);
    }

    public function testCanSearchForContacts(): void
    {
        ContactFactory::createOne([
            'firstName' => 'Horst',
            'lastName' => 'Nacken',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        ContactFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/contacts?search=Horst',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertEquals('Horst', $props['filters']['search']);
        self::assertCount(1, $props['contacts']['data']);
        self::assertEquals('Horst Nacken', $props['contacts']['data'][0]['name']);
    }

    public function testCanNotViewDeletedContacts(): void
    {
        $contact = ContactFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);
        $contact->_delete();

        ContactFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/contacts',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(4, $props['contacts']['data']);
    }

    public function testCanFilterToViewDeletedContacts(): void
    {
        ContactFactory::createOne([
            'firstName' => 'Horst',
            'lastName' => 'Nacken',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        ContactFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/contacts?trashed=with',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertEquals('with', $props['filters']['trashed']);
        self::assertCount(5, $props['contacts']['data']);
    }

    public function testCanCreateNewContact(): void
    {
        /** @var Organization $organization */
        $organization = OrganizationFactory::createOne([
            'name' => 'Some Big Fancy Company Name',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        $contactData = [
            'first_name' => 'Brandon',
            'last_name' => 'Walsh',
            'organization_id' => $organization->getId(),
            'email' => 'brandon@example.com',
            'phone' => '911',
            'address' => '953 Hillcrest Drive',
            'city' => 'Beverly Hills',
            'region' => 'CA',
            'country' => 'US',
            'postal_code' => '90210'
        ];

        $this->client->xmlHttpRequest(
            method: 'POST',
            uri: '/contacts',
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            content: json_encode($contactData, JSON_THROW_ON_ERROR)
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame('/contacts', $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertArrayHasKey('errors', $props);
        self::assertCount(0, $props['errors']);
        self::assertArrayHasKey('flash', $props);
        self::assertArrayHasKey('success', $props['flash']);
        self::assertSame('Contact created.', $props['flash']['success']);
        self::assertCount(1, $props['contacts']['data']);

        $createdContactProps = $props['contacts']['data'][0];

        self::assertNotNull($createdContactProps['id']);
        self::assertSame($contactData['first_name'] . ' ' . $contactData['last_name'], $createdContactProps['name']);
        self::assertSame($contactData['phone'], $createdContactProps['phone']);
        self::assertSame($contactData['city'], $createdContactProps['city']);
        self::assertNull($createdContactProps['deleted_at']);
        self::assertArrayHasKey('organization', $createdContactProps);
        self::assertSame(['name' => 'Some Big Fancy Company Name'], $createdContactProps['organization']);
    }

    public function testCanNotCreateContactWithoutRequiredFields(): void
    {
        $this->client->xmlHttpRequest(
            method: 'POST',
            uri: '/contacts',
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8']
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame('/contacts', $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(2, $props['errors']);
        self::assertArrayHasKey('first_name', $props['errors']);
        self::assertArrayHasKey('last_name', $props['errors']);
        self::assertSame(
            [
                'first_name' => 'This value should not be blank.',
                'last_name' => 'This value should not be blank.'
            ],
            $props['errors']
        );
    }

    public function testCanEditAndUpdateContact(): void
    {
        $contact = ContactFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: sprintf('/contacts/%d/edit', $contact->getId()),
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        // var_dump($response);
        // var_dump($response->getContent());

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(0, $props['errors']);

        $contactProps = $props['contact'];

        self::assertContactPropsAndObjectContentsSame($contactProps, $contact);

        $contactProps['email'] = 'test@example.com';

        $this->client->xmlHttpRequest(
            method: 'PUT',
            uri: sprintf('/contacts/%d', $contact->getId()),
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            content: json_encode($contactProps, JSON_THROW_ON_ERROR)
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertArrayHasKey('flash', $props);
        self::assertArrayHasKey('success', $props['flash']);
        self::assertSame('Contact updated.', $props['flash']['success']);

        self::assertContactPropsAndObjectContentsSame($contactProps, $contact->_refresh());
    }

    public function testCanNotUpdateContactWithoutRequiredFields(): void
    {
        /** @var Contact $contact */
        $contact = ContactFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);

        $url = sprintf('/contacts/%d', $contact->getId());

        $this->client->xmlHttpRequest(
            method: 'PUT',
            uri: $url,
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame($url, $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(2, $props['errors']);
        self::assertArrayHasKey('first_name', $props['errors']);
        self::assertArrayHasKey('last_name', $props['errors']);
        self::assertSame(
            [
                'first_name' => 'This value should not be blank.',
                'last_name' => 'This value should not be blank.'
            ],
            $props['errors']
        );
    }

    /**
     * @param array<string, mixed> $contactProps
     */
    protected static function assertContactPropsAndObjectContentsSame(array $contactProps, Contact $contact): void
    {
        self::assertSame($contact->getId(), $contactProps['id']);
        self::assertSame($contact->getFirstName(), $contactProps['first_name']);
        self::assertSame($contact->getLastName(), $contactProps['last_name']);
        self::assertNull($contactProps['organization_id']);
        self::assertSame($contact->getEmail(), $contactProps['email']);
        self::assertSame($contact->getPhone(), $contactProps['phone']);
        self::assertSame($contact->getAddress(), $contactProps['address']);
        self::assertSame($contact->getCity(), $contactProps['city']);
        self::assertSame($contact->getRegion(), $contactProps['region']);
        self::assertSame($contact->getCountry(), $contactProps['country']);
        self::assertSame($contact->getPostalCode(), $contactProps['postal_code']);
        self::assertNull($contactProps['deleted_at']);
    }
}
