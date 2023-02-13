<?php

namespace App\Entity\SearchManagement;

use App\Entity\LovManagement\Categorie;
use App\Entity\RevueManagement\Block;
use App\Entity\RevueManagement\Page;
use App\Entity\UserManagement\Client;
use App\Repository\SearchManagement\DciRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DciRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Dci
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
     * @ORM\Column(type="text")
     * @Groups({"detail", "list"})
     */
    private $title;

    // mots familières ou mots recherches ou mots à souligner; séparé par un bar vertical
    // $mots
    /**
     * @ORM\ManyToMany(targetEntity=Client::class, mappedBy="dcis", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"name": "ASC"})
     * @Groups({"list"})
     */
    private $clients;

    /**
     * @ORM\ManyToMany(targetEntity=Page::class, mappedBy="dcis")
     * @Groups({"detail", "list"})
     */
    private $pages;

    /**
     * @ORM\ManyToMany(targetEntity=Block::class, mappedBy="dcis")
     * @Groups({"detail", "list"})
     */
    private $blocks;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isIndexed;

    /**
     * @ORM\OneToMany(targetEntity=Indexation::class, mappedBy="dci", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $indexations;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=Categorie::class, inversedBy="dcis")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $categorie;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mots;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->pages = new ArrayCollection();
        $this->blocks = new ArrayCollection();
        $this->indexations = new ArrayCollection();
        $this->isIndexed = false;
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
        if ($this->clients->contains($client)) {
            $this->clients->removeElement($client);
        }

        return $this;
    }

    /**
     * @return Collection|Page[]
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(Page $page): self
    {
        if (!$this->pages->contains($page)) {
            $this->pages[] = $page;
            $page->addDci($this);
        }

        return $this;
    }

    public function removePage(Page $page): self
    {
        if ($this->pages->removeElement($page)) {
            $page->removeDci($this);
        }

        return $this;
    }

    /**
     * @return Collection|Block[]
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): self
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks[] = $block;
            $block->addDci($this);
        }

        return $this;
    }

    public function removeBlock(Block $block): self
    {
        if ($this->blocks->removeElement($block)) {
            $block->removeDci($this);
        }

        return $this;
    }

    public function getNbPage(): int
    {
        $nbPage = 0;
        foreach ($this->getPages() as $page) {
            if (!$page->getIsDeleted()) {
                ++$nbPage;
            }
        }

        return $nbPage;
    }

    public function getNbBlock(): int
    {
        $nbBlock = 0;
        foreach ($this->getBlocks() as $block) {
            if (!$block->getPage()->getIsDeleted()) {
                ++$nbBlock;
            }
        }

        return $nbBlock;
    }

    public function getIsIndexed(): ?bool
    {
        return $this->isIndexed;
    }

    public function setIsIndexed(bool $isIndexed): self
    {
        $this->isIndexed = $isIndexed;

        return $this;
    }

    /**
     * @return Collection|Indexation[]
     */
    public function getIndexations(): Collection
    {
        return $this->indexations;
    }

    public function addIndexation(Indexation $indexation): self
    {
        if (!$this->indexations->contains($indexation)) {
            $this->indexations[] = $indexation;
            $indexation->setDci($this);
        }

        return $this;
    }

    public function removeIndexation(Indexation $indexation): self
    {
        if ($this->indexations->removeElement($indexation)) {
            // set the owning side to null (unless already changed)
            if ($indexation->getDci() === $this) {
                $indexation->setDci(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getMots(): ?string
    {
        return $this->mots;
    }

    public function setMots(?string $mots): self
    {
        $this->mots = $mots;

        return $this;
    }
}
