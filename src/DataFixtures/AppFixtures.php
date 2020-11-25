<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Account;
use App\Factory\ContactFactory;
use App\Factory\OrganizationFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $account = new Account();
        $account->setName('Acme Corporation');

        $manager->persist($account);

        UserFactory::new()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'johndoe@example.com',
            'owner' => true,
            'account' => $account
        ]);

        UserFactory::new()->createMany(5, ['account' => $account]);

        OrganizationFactory::new()->createMany(100, ['account' => $account]);

        ContactFactory::new()
            ->withAttributes(static function (): array {
                return ['organization' => OrganizationFactory::random()];
            })
            ->createMany(100, ['account' => $account]);

        $manager->flush();
    }
}
