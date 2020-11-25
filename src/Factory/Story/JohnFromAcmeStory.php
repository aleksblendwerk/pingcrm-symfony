<?php

declare(strict_types=1);

namespace App\Factory\Story;

use App\Factory\AccountFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

/**
 * Creates one Account named "Acme Corporation"
 * Creates one User named "John Doe", e-mail "johndoe@example.com", belonging to account "Acme Corporation"
 */
class JohnFromAcmeStory extends Story
{
    public function build(): void
    {
        $this->add('acme', AccountFactory::new()->create(['name' => 'Acme Corporation']));

        $this->add(
            'john',
            UserFactory::new()->create([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'johndoe@example.com',
                'owner' => true,
                'account' => $this->get('acme')
            ])
        );
    }
}
