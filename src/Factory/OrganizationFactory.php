<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Organization;
use Zenstruck\Foundry\ModelFactory;

/**
 * @extends ModelFactory<Organization>
 */
class OrganizationFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
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

    protected static function getClass(): string
    {
        return Organization::class;
    }
}
