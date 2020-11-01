<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zenstruck\Foundry\ModelFactory;

final class UserFactory extends ModelFactory
{
    protected UserPasswordEncoderInterface $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        parent::__construct();

        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'firstName' => self::faker()->firstName,
            'lastName' => self::faker()->lastName,
            'email' => self::faker()->unique()->safeEmail,
            'password' => 'secret',
            // 'remember_token' => Str::random(10)
            // 'owner' => false
            'roles' => []
        ];
    }

    /**
     * @return static
     */
    protected function initialize(): self
    {
        // @phpstan-ignore-next-line (seems to be related to https://github.com/phpstan/phpstan/issues/3523)
        return $this->afterInstantiate(function (User $user): void {
            if ($user->getPassword() === null) {
                return;
            }

            $user->setPassword($this->userPasswordEncoder->encodePassword($user, $user->getPassword()));
        });
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
