<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Account;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Account>
 */
class AccountFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company()
        ];
    }

    public static function class(): string
    {
        return Account::class;
    }
}
