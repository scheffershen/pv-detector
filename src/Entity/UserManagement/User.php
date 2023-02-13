<?php

namespace App\Entity\UserManagement;

use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsEnableTrait;
use App\Validator\UserManagement\ComplexPassword; 
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\UserRepository")
 * @ORM\Table(name="user")
 * @UniqueEntity("email")
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class User implements UserInterface
{
    public const ROLES = [
                            'Client'   => 'ROLE_CLIENT',
                            'Lecteur' => 'ROLE_LECTEUR',
                            'Gestionnaire' => 'ROLE_GESTIONNAIRE',
                            'Admin' => 'ROLE_ADMIN',
                         ];

    use ActorTrait;
    use DateTrait;
    use IsEnableTrait;
    use IsDeletedTrait;

    /**
     * Password requests older than this many seconds will be considered expired. une demande toutes les 15min=900s 
     */
    public const RETRY_TTL = 900;

    /**
     * Maximum time that the confirmation token will be valid. lien actif 30min=1800s 
     */
    public const TOKEN_TTL = 1800;
    
    /**
     * Minimum day for changing the password.
     */
    public const PASSWORD_AGE = 30;
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=50)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=100, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 100,
     *      minMessage = "Your first name must be at least {{ limit }} characters long",
     *      maxMessage = "Your first name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\OrderBy({"firstname" = "DESC"})
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 100,
     *      minMessage = "Your last name must be at least {{ limit }} characters long",
     *      maxMessage = "Your last name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\OrderBy({"lastname" = "DESC"})
     */
    protected $lastname;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @ComplexPassword(groups={"PasswordReset"}) 
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $confirmation_token;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $password_requested_at;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fullname;

    /**
     * @var bool
     *
     * @ORM\Column(name="change_password", type="boolean", nullable=false)
     */
    protected $change_password = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_change_password", type="datetime", nullable=true)
     */
    protected $lastChangePassword;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $fax;

    /**
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="respondableClient")
     */
    private $clientsResponsable;

    /**
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="backupResponsableClient")
     */
    private $clientsBackupResponsable;

    /**
     * @ORM\ManyToMany(targetEntity=Client::class, inversedBy="users")
     */
    private $clients;

    public function __construct()
    {
        $this->clientsResponsable = new ArrayCollection();
        $this->clientsBackupResponsable = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->lastChangePassword = null;
    }

    public function __toString()
    {
        if (!empty($this->lastname) && !empty($this->firstname)) {
            return $this->firstname.' '.$this->lastname;
        }
        if (!empty($this->fullname)) {
            return $this->fullname;
        }

        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmation_token;
    }

    public function setConfirmationToken(?string $confirmation_token): self
    {
        $this->confirmation_token = $confirmation_token;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->password_requested_at;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $password_requested_at): self
    {
        $this->password_requested_at = $password_requested_at;

        return $this;
    }

    public function isPasswordRequestNonExpired(int $ttl): bool
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    public function getFullname(): ?string
    {
        if (!empty($this->fullname)) {
            return $this->fullname;
        } else {
            return $this->firstname.' '.$this->lastname;
        }
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Set change_password.
     *
     * @return self
     */
    public function setChangePassword(bool $change_password): ?self
    {
        $this->change_password = $change_password;

        return $this;
    }

    /**
     * Get change_password.
     *
     * @return bool
     */
    public function getChangePassword(): ?bool
    {
        return $this->change_password;
    }

    /**
     * Set lastChangePassword
     *
     * @param \DateTime $lastChangePassword
     * @return User
     */
    public function setLastChangePassword(?\DateTimeInterface $lastChangePassword): self
    {
        $this->lastChangePassword = $lastChangePassword;

        return $this;
    }

    /**
     * Get lastChangePassword
     *
     * @return \DateTime
     */
    public function getLastChangePassword(): ?\DateTimeInterface
    {
        return $this->lastChangePassword;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClientsResponsable(): Collection
    {
        return $this->clientsResponsable;
    }

    public function addClientsResponsable(Client $clientsResponsable): self
    {
        if (!$this->clientsResponsable->contains($clientsResponsable)) {
            $this->clientsResponsable[] = $clientsResponsable;
            $clientsResponsable->setRespondableClient($this);
        }

        return $this;
    }

    public function removeClientsResponsable(Client $clientsResponsable): self
    {
        if ($this->clientsResponsable->removeElement($clientsResponsable)) {
            // set the owning side to null (unless already changed)
            if ($clientsResponsable->getRespondableClient() === $this) {
                $clientsResponsable->setRespondableClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClientsBackupResponsable(): Collection
    {
        return $this->clientsBackupResponsable;
    }

    public function addClientsBackupResponsable(Client $clientsBackupResponsable): self
    {
        if (!$this->clientsBackupResponsable->contains($clientsBackupResponsable)) {
            $this->clientsBackupResponsable[] = $clientsBackupResponsable;
            $clientsBackupResponsable->setBackupResponsableClient($this);
        }

        return $this;
    }

    public function removeClientsBackupResponsable(Client $clientsBackupResponsable): self
    {
        if ($this->clientsBackupResponsable->removeElement($clientsBackupResponsable)) {
            // set the owning side to null (unless already changed)
            if ($clientsBackupResponsable->getBackupResponsableClient() === $this) {
                $clientsBackupResponsable->setBackupResponsableClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        $this->clients->removeElement($client);

        return $this;
    }

}
