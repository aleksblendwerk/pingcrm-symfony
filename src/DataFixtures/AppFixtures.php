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

        // TODO: this seems odd, let's investigate this some time...
        $manager->persist($account);
        $manager->flush();

        $manager->refresh($account);

        UserFactory::new()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'johndoe@example.com',
            'owner' => true,
            'account' => $account
        ]);

        UserFactory::new()::createMany(5, ['account' => $account]);

        OrganizationFactory::new()::createMany(100, ['account' => $account]);

        ContactFactory::createMany(
            100,
            function () use ($account) {
                return [
                    'account' => $account,
                    'organization' => OrganizationFactory::random()
                ];
            }
        );

        $manager->flush();
    }
}
