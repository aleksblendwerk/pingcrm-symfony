<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Organization;
use App\Factory\ContactFactory;
use App\Factory\OrganizationFactory;
use App\Factory\Story\JohnFromAcmeStory;
use App\Tests\InertiaTestCase;

class OrganizationControllerTest extends InertiaTestCase
{
    public function testCanViewOrganizations(): void
    {
        OrganizationFactory::createMany(5, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/organizations',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(5, $props['organizations']['data']);
    }

    public function testCanSearchForOrganizations(): void
    {
        OrganizationFactory::createOne([
            'name' => 'Some Big Fancy Company Name',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        OrganizationFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/organizations?search=Some Big Fancy Company Name',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertEquals('Some Big Fancy Company Name', $props['filters']['search']);
        self::assertCount(1, $props['organizations']['data']);
        self::assertEquals('Some Big Fancy Company Name', $props['organizations']['data'][0]['name']);
    }

    public function testCanNotViewDeletedOrganizations(): void
    {
        $organization = OrganizationFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);
        $organization->delete();

        OrganizationFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/organizations',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(4, $props['organizations']['data']);
    }

    public function testCanFilterToViewDeletedOrganizations(): void
    {
        OrganizationFactory::createOne([
            'name' => 'Some Big Fancy Company Name',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        OrganizationFactory::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: '/organizations?trashed=with',
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertEquals('with', $props['filters']['trashed']);
        self::assertCount(5, $props['organizations']['data']);
    }

    public function testCanCreateNewOrganization(): void
    {
        $organizationData = [
            'name' => 'Casa Walsh',
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
            uri: '/organizations',
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            content: json_encode($organizationData, JSON_THROW_ON_ERROR)
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame('/organizations', $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertArrayHasKey('errors', $props);
        self::assertCount(0, $props['errors']);
        self::assertArrayHasKey('flash', $props);
        self::assertArrayHasKey('success', $props['flash']);
        self::assertSame('Organization created.', $props['flash']['success']);
        self::assertCount(1, $props['organizations']['data']);

        $createdOrganizationProps = $props['organizations']['data'][0];

        self::assertNotNull($createdOrganizationProps['id']);
        self::assertSame($organizationData['name'], $createdOrganizationProps['name']);
        self::assertSame($organizationData['phone'], $createdOrganizationProps['phone']);
        self::assertSame($organizationData['city'], $createdOrganizationProps['city']);
        self::assertNull($createdOrganizationProps['deleted_at']);
    }

    public function testCanNotCreateOrganizationWithoutRequiredFields(): void
    {
        $this->client->xmlHttpRequest(
            method: 'POST',
            uri: '/organizations',
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8']
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame('/organizations', $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(1, $props['errors']);
        self::assertArrayHasKey('name', $props['errors']);
        self::assertSame(['name' => 'This value should not be blank.'], $props['errors']);
    }

    public function testCanEditAndUpdateOrganization(): void
    {
        $organization = OrganizationFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            method: 'GET',
            uri: sprintf('/organizations/%d/edit', $organization->getId()),
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(0, $props['errors']);

        $organizationProps = $props['organization'];

        self::assertOrganizationPropsAndObjectContentsSame($organizationProps, $organization);

        $organizationProps['email'] = 'test@example.com';

        $this->client->xmlHttpRequest(
            method: 'PUT',
            uri: sprintf('/organizations/%d', $organization->getId()),
            server: ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            content: json_encode($organizationProps, JSON_THROW_ON_ERROR)
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertArrayHasKey('flash', $props);
        self::assertArrayHasKey('success', $props['flash']);
        self::assertSame('Organization updated.', $props['flash']['success']);

        self::assertOrganizationPropsAndObjectContentsSame($organizationProps, $organization->_refresh());
    }

    public function testCanNotUpdateOrganizationWithoutRequiredFields(): void
    {
        /** @var Organization $organization */
        $organization = OrganizationFactory::createOne(['account' => JohnFromAcmeStory::load()::get('acme')]);

        $url = sprintf('/organizations/%d', $organization->getId());

        $this->client->xmlHttpRequest(
            method: 'PUT',
            uri: $url,
            server: ['HTTP_X-Inertia' => true]
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame($url, $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(1, $props['errors']);
        self::assertArrayHasKey('name', $props['errors']);
        self::assertSame(['name' => 'This value should not be blank.'], $props['errors']);
    }

    /**
     * @param array<string, mixed> $organizationProps
     */
    protected static function assertOrganizationPropsAndObjectContentsSame(
        array $organizationProps,
        Organization $organization
    ): void {
        self::assertSame($organization->getId(), $organizationProps['id']);
        self::assertSame($organization->getName(), $organizationProps['name']);
        self::assertSame($organization->getEmail(), $organizationProps['email']);
        self::assertSame($organization->getPhone(), $organizationProps['phone']);
        self::assertSame($organization->getAddress(), $organizationProps['address']);
        self::assertSame($organization->getCity(), $organizationProps['city']);
        self::assertSame($organization->getRegion(), $organizationProps['region']);
        self::assertSame($organization->getCountry(), $organizationProps['country']);
        self::assertSame($organization->getPostalCode(), $organizationProps['postal_code']);
        self::assertNull($organizationProps['deleted_at']);
        self::assertCount(0, $organizationProps['contacts']);
    }
}
