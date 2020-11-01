<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ContactRepository::class)
 * @ORM\Table(name="contacts")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 */
class Contact
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false)
     */
    private Account $account;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="contacts")
     */
    private ?Organization $organization = null;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private string $firstName;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private string $lastName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $phone = null;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private ?string $address = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $city = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $region = null;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private ?string $country = null;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private ?string $postalCode = null;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getAccount(): ?Account
    {
        return $this->account ?? null;
    }

    public function setAccount(?Account $account): void
    {
        if ($account === null) {
            unset($this->account);

            return;
        }

        $this->account = $account;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }
}
