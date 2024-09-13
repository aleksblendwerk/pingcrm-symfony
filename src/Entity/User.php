<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[UniqueEntity('email')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class User implements
    PasswordAuthenticatedUserInterface,
    UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Email]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 25)]
    #[ORM\Column(length: 25)]
    private ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 25)]
    #[ORM\Column(length: 25)]
    private ?string $lastName = null;

    /**
     * The hashed password
     */
    #[Assert\NotBlank(normalizer: 'trim')]
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $owner = false;

    #[Assert\Image(mimeTypes: ['image/jpeg', 'image/png'], minWidth: 1, minHeight: 1)]
    #[Vich\UploadableField(mapping: 'user_photo', fileNameProperty: 'photoFilename')]
    private ?File $photoFile = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $photoFilename = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    #[Gedmo\Timestampable]
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    #[Gedmo\Timestampable]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $deletedAt = null;

    #[Assert\NotNull]
    #[Assert\Type(Account::class)]
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getName(): string
    {
        return trim(sprintf('%s %s', $this->getFirstName(), $this->getLastName()));
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<int, string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function isOwner(): bool
    {
        return $this->owner;
    }

    public function setOwner(bool $owner): void
    {
        $this->owner = $owner;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;

        if ($photoFile === null) {
            return;
        }

        $this->updatedAt = new \DateTime();
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): void
    {
        $this->photoFilename = $photoFilename;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    /**
     * Returns the identifier for this user (e.g. its username or email address).
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->getEmail() ?? 'Unknown User';
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword()
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (
            count($data) === 0 ||
            !array_key_exists('id', $data) ||
            !array_key_exists('email', $data) ||
            !array_key_exists('password', $data) ||
            !is_int($data['id']) ||
            !is_string($data['email']) ||
            !is_string($data['password'])
        ) {
            throw new \RuntimeException('Unable to unserialize user!');
        }

        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
}
