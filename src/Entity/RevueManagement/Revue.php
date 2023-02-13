<?php

namespace App\Entity\RevueManagement;

use App\Entity\LovManagement\Access;
use App\Entity\UserManagement\Client;
use App\Repository\RevueManagement\RevueRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DatetimeInterface;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RevueRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Revue
{
    use ActorTrait;
    use DateTrait;
    use IsValidTrait;
    use IsDeletedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=True)
     */
    private $numero;

    /**
     * @ORM\ManyToOne(targetEntity=Access::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $access;

    /**
     * @ORM\Column(type="string", length=256, nullable=True)
     */
    private $site;

    /**
     * @ORM\ManyToMany(targetEntity=Client::class, mappedBy="revues")
     * @ORM\OrderBy({"name": "ASC"})
     */
    protected $clients;

    /**
     * @ORM\OneToMany(targetEntity=Numero::class, mappedBy="revue", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"receiptDate" = "DESC"})
     */
    private $numeros;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $editeur;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $periodicity;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCFC;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $contact;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->numeros = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getEndDate(): ?DatetimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DatetimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getAccess(): ?Access
    {
        return $this->access;
    }

    public function setAccess(?Access $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function setSite(string $site): self
    {
        $this->site = $site;

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
            $client->addRevue($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->contains($client)) {
            $this->clients->removeElement($client);
            $client->removeRevue($this);
        }

        return $this;
    }

    /**
     * @return Collection|Numero[]
     */
    public function getNumeros(): Collection
    {
        return $this->numeros;
    }

    public function addNumero(Numero $numero): self
    {
        if (!$this->numeros->contains($numero)) {
            $this->numeros[] = $numero;
            $numero->setRevue($this);
        }

        return $this;
    }

    public function removeNumero(Numero $numero): self
    {
        if ($this->numeros->contains($numero)) {
            $this->numeros->removeElement($numero);
            // set the owning side to null (unless already changed)
            if ($numero->getRevue() === $this) {
                $numero->setRevue(null);
            }
        }

        return $this;
    }

    public function getEditeur(): ?string
    {
        return $this->editeur;
    }

    public function setEditeur(string $editeur): self
    {
        $this->editeur = $editeur;

        return $this;
    }

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(?string $periodicity): self
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    public function getIsCFC(): ?bool
    {
        return $this->isCFC;
    }

    public function setIsCFC(bool $isCFC): self
    {
        $this->isCFC = $isCFC;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function hasNombreNumeroValid(): int
    {
        $nbNumero = 0;
        foreach ($this->getNumeros() as $numero) {
            if ($numero->getIsValid()) {
                ++$nbNumero;
            }
        }

        return $nbNumero;
    }
}
