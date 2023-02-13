<?php

namespace App\Entity\RevueManagement;

use App\Entity\SearchManagement\Dci;
use App\Repository\RevueManagement\BlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BlockRepository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"text"}, flags={"fulltext"})})
 */
class Block
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $confidence;

    /**
     * @ORM\ManyToOne(targetEntity=Page::class, inversedBy="blocks")
     */
    private $page;

    /**
     * @ORM\OneToMany(targetEntity=Vertice::class, mappedBy="block", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $vertices;

    /**
     * @ORM\ManyToMany(targetEntity=Dci::class, inversedBy="blocks")
     */
    private $dcis;

    private $found = false;

    public function __construct()
    {
        $this->vertices = new ArrayCollection();
        $this->dcis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    public function setConfidence(?float $confidence): self
    {
        $this->confidence = $confidence;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return Collection|Vertice[]
     */
    public function getVertices(): Collection
    {
        return $this->vertices;
    }

    public function addVertex(Vertice $vertex): self
    {
        if (!$this->vertices->contains($vertex)) {
            $this->vertices[] = $vertex;
            $vertex->setBlock($this);
        }

        return $this;
    }

    public function removeVertex(Vertice $vertex): self
    {
        if ($this->vertices->removeElement($vertex)) {
            // set the owning side to null (unless already changed)
            if ($vertex->getBlock() === $this) {
                $vertex->setBlock(null);
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

    public function getLeft(): int
    {
        $vertices = $this->getVertices();

        return $vertices[0]->getX();
    }

    public function getTop(): int
    {
        $vertices = $this->getVertices();

        return $vertices[0]->getY();
    }

    public function getWidth(): int
    {
        $vertices = $this->getVertices();

        return $vertices[1]->getX() - $vertices[0]->getX();
    }

    public function getHeight(): int
    {
        $vertices = $this->getVertices();

        return $vertices[2]->getY() - $vertices[0]->getY();
    }

    public function getFound(): bool
    {
        return $this->found;
    }

    public function setFound(bool $found): self
    {
        $this->found = $found;

        return $this;
    }
}
