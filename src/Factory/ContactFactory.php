<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Contact;
use Zenstruck\Foundry\ModelFactory;

final class ContactFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'firstName' => self::faker()->firstName,
            'lastName' => self::faker()->lastName,
            'email' => self::faker()->unique()->safeEmail,
            'phone' => self::faker()->phoneNumber,
            'address' => self::faker()->streetAddress,
            'city' => self::faker()->city,
            'region' => self::faker()->state,
            'country' => 'US',
            'postalCode' => self::faker()->postcode
        ];
    }

    protected static function getClass(): string
    {
        return Contact::class;
    }
}
