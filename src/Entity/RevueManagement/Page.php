<?php

namespace App\Entity\RevueManagement;

use App\Entity\SearchManagement\Dci;
use App\Entity\SearchManagement\Indexation;
use App\Repository\RevueManagement\PageRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PageRepository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"content"}, flags={"fulltext"}), @ORM\Index(columns={"blocks_text"}, flags={"fulltext"})})
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Page
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
     * @ORM\Column(type="integer")
     */
    private $numeroPage;

    /**
     * @ORM\ManyToOne(targetEntity=Numero::class, inversedBy="pages")
     */
    private $numero;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=Image::class, inversedBy="pages")
     */
    private $image;

    /**
     * @ORM\OneToMany(targetEntity=Block::class, mappedBy="page", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $blocks;

    /**
     * @ORM\ManyToMany(targetEntity=Dci::class, inversedBy="pages")
     */
    private $dcis;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $blocksText;

    /**
     * @ORM\OneToMany(targetEntity=Indexation::class, mappedBy="page", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $indexations;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
        $this->dcis = new ArrayCollection();
        $this->indexations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?Numero
    {
        return $this->numero;
    }

    public function setNumero(?Numero $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getNumeroPage(): ?string
    {
        return $this->numeroPage;
    }

    public function setNumeroPage(string $numeroPage): self
    {
        $this->numeroPage = $numeroPage;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

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
            $block->setPage($this);
        }

        return $this;
    }

    public function removeBlock(Block $block): self
    {
        if ($this->blocks->removeElement($block)) {
            // set the owning side to null (unless already changed)
            if ($block->getPage() === $this) {
                $block->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Dci[]
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
        $this->dcis->removeElement($dci);

        return $this;
    }

    public function getBlocksText(): ?string
    {
        return $this->blocksText;
    }

    public function setBlocksText(?string $blocksText): self
    {
        $this->blocksText = $blocksText;

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
            $indexation->setPage($this);
        }

        return $this;
    }

    public function removeIndexation(Indexation $indexation): self
    {
        if ($this->indexations->removeElement($indexation)) {
            // set the owning side to null (unless already changed)
            if ($indexation->getPage() === $this) {
                $indexation->setPage(null);
            }
        }

        return $this;
    }
}
