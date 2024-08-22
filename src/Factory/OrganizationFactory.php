<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Organization;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Organization>
 */
class OrganizationFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company(),
            'email' => self::faker()->companyEmail(),
            'phone' => self::faker()->phoneNumber(),
            'address' => self::faker()->streetAddress(),
            'city' => self::faker()->city(),
            'region' => self::faker()->state(),
            'country' => 'US',
            'postalCode' => self::faker()->postcode()
        ];
    }

    public static function class(): string
    {
        return Organization::class;
    }
}
