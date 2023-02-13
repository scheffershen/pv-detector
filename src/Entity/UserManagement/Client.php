<?php

namespace App\Entity\UserManagement;

use App\Entity\LovManagement\Jour;
use App\Entity\RevueManagement\Revue;
use App\Entity\SearchManagement\Dci;
use App\Repository\UserManagement\ClientRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="name", message="Client name is already taken.")
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 * @Vich\Uploadable
 */
class Client
{
    use ActorTrait;
    use DateTrait;
    use IsValidTrait;
    use IsDeletedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"detail", "list"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"detail", "list"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", nullable=false)
     */
    protected $code;

    /**
     * maybe late.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"detail", "list"})
     */
    private $adress;

    /**
     * maybe late.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"detail", "list"})
     */
    private $logoUri;

    /**
     * maybe late.
     *
     * @Vich\UploadableField(mapping="uploads_private", fileNameProperty="logoUri")
     *
     * @var File
     */
    protected $logo;

    /**
     * @ORM\ManyToMany(targetEntity=Dci::class, inversedBy="clients")
     * @Groups({"detail", "list"})
     */
    private $dcis;

    /**
     * @ORM\ManyToMany(targetEntity=Revue::class, inversedBy="clients")
     * @ORM\OrderBy({"title": "ASC"})
     * @Groups({"detail", "list"})
     */
    private $revues;

    /**
     * @ORM\ManyToOne(targetEntity=Jour::class)
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $jourBilanHebdomadaire;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="clientsResponsable")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $respondableClient;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="clientsBackupResponsable")
     */
    private $backupResponsableClient;

    /**
     * @ORM\OneToMany(targetEntity=Lien::class, mappedBy="client", cascade={"persist"}, orphanRemoval=TRUE)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $liens;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="clients")
     */
    private $users;

    public function __construct()
    {
        $this->dcis = new ArrayCollection();
        $this->revues = new ArrayCollection();
        $this->liens = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): self
    {
        $this->adress = $adress;

        return $this;
    }

    public function getLogoUri(): ?string
    {
        return $this->logoUri;
    }

    public function setLogoUri(?string $logoUri): self
    {
        $this->logoUri = $logoUri;

        return $this;
    }

    public function getLogo(): ?File
    {
        return $this->logo;
    }

    public function setLogo(File $logo = null): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection|dci[]
     */
    public function getDcis(): Collection
    {
        return $this->dcis;
    }

    public function addDci(Dci $dci): self
    {
        if (!$this->dcis->contains($dci)) {
            $this->dcis[] = $dci;
        }

        return $this;
    }

    public function removeDci(Dci $dci): self
    {
        if ($this->dcis->contains($dci)) {
            $this->dcis->removeElement($dci);
        }

        return $this;
    }

    public function getNbDcis(): int
    {
        $nbDcis = 0;
        foreach ($this->getDcis() as $dci) {
            if ($dci->getIsValid()) {
                ++$nbDcis;
            }
        }

        return $nbDcis;
    }

    /**
     * @return Collection|Revue[]
     */
    public function getRevues(): Collection
    {
        return $this->revues;
    }

    public function addRevue(Revue $revue): self
    {
        if (!$this->revues->contains($revue)) {
            $this->revues[] = $revue;
            $revue->addClient($this);
        }

        return $this;
    }

    public function removeRevue(Revue $revue): self
    {
        if ($this->revues->contains($revue)) {
            $this->revues->removeElement($revue);
            $revue->removeClient($this);
        }

        return $this;
    }

    public function getNbRevues(): int
    {
        $nbRevues = 0;
        foreach ($this->getRevues() as $revue) {
            if ($revue->getIsValid()) {
                ++$nbRevues;
            }
        }

        return $nbRevues;
    }

    public function getJourBilanHebdomadaire(): ?Jour
    {
        return $this->jourBilanHebdomadaire;
    }

    public function setJourBilanHebdomadaire(?Jour $jourBilanHebdomadaire): self
    {
        $this->jourBilanHebdomadaire = $jourBilanHebdomadaire;

        return $this;
    }

    public function getRespondableClient(): ?User
    {
        return $this->respondableClient;
    }

    public function setRespondableClient(?User $respondableClient): self
    {
        $this->respondableClient = $respondableClient;

        return $this;
    }

    public function getBackupResponsableClient(): ?User
    {
        return $this->backupResponsableClient;
    }

    public function setBackupResponsableClient(?User $backupResponsableClient): self
    {
        $this->backupResponsableClient = $backupResponsableClient;

        return $this;
    }

    /**
     * @return Collection|Lien[]
     */
    public function getLiens(): Collection
    {
        return $this->liens;
    }

    public function addLien(Lien $lien): self
    {
        if (!$this->liens->contains($lien)) {
            $this->liens[] = $lien;
            $lien->setClient($this);
        }

        return $this;
    }

    public function removeLien(Lien $lien): self
    {
        if ($this->liens->removeElement($lien)) {
            // set the owning side to null (unless already changed)
            if ($lien->getClient() === $this) {
                $lien->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addClient($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeClient($this);
        }

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
    }
    
}
