<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Organization;
use App\Factory\OrganizationFactory;
use App\Factory\Story\JohnFromAcmeStory;
use App\Tests\InertiaTestCase;

class OrganizationControllerTest extends InertiaTestCase
{
    public function testCanViewOrganizations(): void
    {
        OrganizationFactory::new()::createMany(5, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest('GET', '/organizations', [], [], ['HTTP_X-Inertia' => true]);

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(5, $props['organizations']['data']);
    }

    public function testCanSearchForOrganizations(): void
    {
        OrganizationFactory::new()->create([
            'name' => 'Some Big Fancy Company Name',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ]);

        OrganizationFactory::new()::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest(
            'GET',
            '/organizations?search=Some Big Fancy Company Name',
            [],
            [],
            ['HTTP_X-Inertia' => true]
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
        OrganizationFactory::new()->create([
            'name' => 'Some Big Fancy Company Name',
            'account' => JohnFromAcmeStory::load()::get('acme')
        ])->remove();

        OrganizationFactory::new()::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest('GET', '/organizations', [], [], ['HTTP_X-Inertia' => true]);

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(4, $props['organizations']['data']);
    }

    public function testCanFilterToViewDeletedOrganizations(): void
    {
        OrganizationFactory::new()->create([
           'name' => 'Some Big Fancy Company Name',
           'account' => JohnFromAcmeStory::load()::get('acme')
        ])->remove();

        OrganizationFactory::new()::createMany(4, ['account' => JohnFromAcmeStory::load()::get('acme')]);

        $this->client->xmlHttpRequest('GET', '/organizations?trashed=with', [], [], ['HTTP_X-Inertia' => true]);

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
            'POST',
            '/organizations/create',
            [],
            [],
            ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            json_encode($organizationData, JSON_THROW_ON_ERROR)
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
            'POST',
            '/organizations/create',
            [],
            [],
            ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            null
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame('/organizations/create', $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(1, $props['errors']);
        self::assertArrayHasKey('name', $props['errors']);
        self::assertSame(['name' => 'This value should not be blank.'], $props['errors']);
    }

    public function testCanEditAndUpdateOrganization(): void
    {
        $organizationProxy = OrganizationFactory::new()->create(['account' => JohnFromAcmeStory::load()::get('acme')]);

        /** @var Organization $organization */
        $organization = $organizationProxy->object();

        $url = sprintf('/organizations/%d/edit', $organization->getId());

        $this->client->xmlHttpRequest('GET', $url, [], [], ['HTTP_X-Inertia' => true]);

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertCount(0, $props['errors']);

        $organizationProps = $props['organization'];

        self::assertOrganizationPropsAndObjectContentsSame($organizationProps, $organization);

        $organizationProps['email'] = 'test@example.com';

        $this->client->xmlHttpRequest(
            'POST',
            $url,
            [],
            [],
            ['HTTP_X-Inertia' => true, 'CONTENT_TYPE' => 'application/json;charset=UTF-8'],
            json_encode($organizationProps, JSON_THROW_ON_ERROR)
        );

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());

        $props = self::getPropsFromResponse($response);

        self::assertArrayHasKey('flash', $props);
        self::assertArrayHasKey('success', $props['flash']);
        self::assertSame('Organization updated.', $props['flash']['success']);

        /** @var Organization $organization */
        $organization = $organizationProxy->refresh()->object(); // get the refreshed entity

        self::assertOrganizationPropsAndObjectContentsSame($organizationProps, $organization);
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

    public function testCanNotUpdateOrganizationWithoutRequiredFields(): void
    {
        /** @var Organization $organization */
        $organization = OrganizationFactory::new()
            ->create(['account' => JohnFromAcmeStory::load()::get('acme')])
            ->object();

        $url = sprintf('/organizations/%d/edit', $organization->getId());

        $this->client->xmlHttpRequest('POST', $url, [], [], ['HTTP_X-Inertia' => true]);

        $response = $this->client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertSame($url, $this->client->getRequest()->getRequestUri());

        $props = self::getPropsFromResponse($response);

        self::assertCount(1, $props['errors']);
        self::assertArrayHasKey('name', $props['errors']);
        self::assertSame(['name' => 'This value should not be blank.'], $props['errors']);
    }
}
