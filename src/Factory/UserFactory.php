<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->unique()->safeEmail(),
            'owner' => false,
            'password' => 'secret'
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            if ($user->getPassword() === null) {
                return;
            }

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPassword()));
        });
    }

    public static function class(): string
    {
        return User::class;
    }
}
