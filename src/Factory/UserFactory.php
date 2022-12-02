<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;

class UserFactory extends ModelFactory
{
    /**
     * @var array<string, string>
     */
    protected array $knownPasswordHashes = [
        'secret' => '$argon2id$v=19$m=65536,t=4,p=1$ux1PEx9u0ynZ5KJG7k4xwA$0yBw3YwKSkI9nxr/djTS9FN86q1vveCM+vNUJBS8nFw'
    ];

    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->unique()->safeEmail(),
            'owner' => false,
            'password' => 'secret'
        ];
    }

    /**
     * @return static
     */
    protected function initialize(): self
    {
        return $this->afterInstantiate(function (User $user): void {
            if ($user->getPassword() === null) {
                return;
            }

            if (array_key_exists($user->getPassword(), $this->knownPasswordHashes)) {
                $user->setPassword($this->knownPasswordHashes[$user->getPassword()]);

                return;
            }

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPassword()));
        });
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
