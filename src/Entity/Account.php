<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=AccountRepository::class)
 * @ORM\Table(name="accounts")
 */
class Account
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $name;

    /**
     * @var Collection<int, Organization>
     *
     * @ORM\OneToMany(targetEntity=Organization::class, mappedBy="account")
     */
    private Collection $organizations;

    /**
     * @var Collection<int, Contact>
     *
     * @ORM\OneToMany(targetEntity=Contact::class, mappedBy="account", orphanRemoval=true)
     */
    private Collection $contacts;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization): void
    {
        if ($this->organizations->contains($organization)) {
            return;
        }

        $this->organizations[] = $organization;
        $organization->setAccount($this);
    }

    public function removeOrganization(Organization $organization): void
    {
        if (!$this->organizations->removeElement($organization)) {
            return;
        }

        // set the owning side to null (unless already changed)
        if ($organization->getAccount() !== $this) {
            return;
        }

        $organization->setAccount(null);
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setAccount($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getAccount() === $this) {
                $contact->setAccount(null);
            }
        }

        return $this;
    }
}
