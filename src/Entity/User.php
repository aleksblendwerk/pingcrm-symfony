<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 * @UniqueEntity("email")
 * @Vich\Uploadable()
 */
class User implements UserInterface, \Serializable
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
     * @ORM\Column(type="string", length=50, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=50)
     * @Assert\Email()
     */
    private string $email;

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
     * The hashed password
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(normalizer="trim")
     */
    private string $password;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     */
    private bool $owner = false;

    /**
     * @Assert\Image(mimeTypes={"image/jpeg", "image/png"}, minWidth=1, minHeight=1)
     * @Vich\UploadableField(mapping="user_photo", fileNameProperty="photoFilename")
     */
    private ?File $photoFile = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $photoFilename = null;

    /**
     * @var array<int, string>
     *
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     * @Assert\Type("App\Entity\Account")
     */
    private Account $account;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->email ?? null;
    }

    public function setEmail(?string $email): void
    {
        if ($email === null) {
            unset($this->email);

            return;
        }

        $this->email = $email;
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

    public function getPassword(): ?string
    {
        return $this->password ?? null;
    }

    public function setPassword(?string $password): void
    {
        if ($password === null) {
            unset($this->password);

            return;
        }

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->getEmail() ?? 'Unknown User';
    }

    public function serialize(): string
    {
        return serialize([$this->getId(), $this->getEmail(), $this->getPassword()]);
    }

    /**
     * @param string $serialized
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    public function unserialize($serialized): void
    {
        [$this->id, $this->email, $this->password] = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }
}
