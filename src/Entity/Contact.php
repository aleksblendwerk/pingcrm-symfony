<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\SoftDeletableInterface;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletableTrait;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ContactRepository::class)
 * @ORM\Table(name="contacts")
 */
class Contact implements SoftDeletableInterface, TimestampableInterface
{
    use SoftDeletableTrait;
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     * @Assert\Type("App\Entity\Account")
     */
    private Account $account;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="contacts")
     */
    private ?Organization $organization = null;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlank()
     * @Assert\Length(max=25)
     */
    private string $firstName;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlank()
     * @Assert\Length(max=25)
     */
    private string $lastName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     * @Assert\Email()
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private ?string $phone = null;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     * @Assert\Length(max=150)
     */
    private ?string $address = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private ?string $city = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private ?string $region = null;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Assert\Length(max=2)
     */
    private ?string $country = null;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Assert\Length(max=25)
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
        return $this->firstName ?? null;
    }

    public function setFirstName(?string $firstName): void
    {
        if ($firstName === null) {
            unset($this->firstName);

            return;
        }

        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName ?? null;
    }

    public function setLastName(?string $lastName): void
    {
        if ($lastName === null) {
            unset($this->lastName);

            return;
        }

        $this->lastName = $lastName;
    }

    public function getName(): string
    {
        return sprintf('%s %s', $this->getFirstName(), $this->getLastName());
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
