<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Contact;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Contact>
 */
class ContactFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->unique()->safeEmail(),
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
        return Contact::class;
    }
}
