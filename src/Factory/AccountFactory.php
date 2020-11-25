<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Account;
use Zenstruck\Foundry\ModelFactory;

class AccountFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->company
        ];
    }

    protected static function getClass(): string
    {
        return Account::class;
    }
}
