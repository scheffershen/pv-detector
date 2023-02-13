<?php

namespace App\Entity\SearchManagement;

use App\Entity\RevueManagement\Image;
use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Page;
use App\Repository\SearchManagement\IndexationRepository;
use App\Traits\DateTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=IndexationRepository::class)
 * @ORM\Table(name="indexation",uniqueConstraints={
 *          @ORM\UniqueConstraint(name="search_idx",
 *              columns={"numero_id", "image_id", "page_id", "dci_id"})
 *      })
 * @ORM\HasLifecycleCallbacks()
 */
class Indexation
{
    use DateTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Numero::class, inversedBy="indexations")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $numero;

    /**
     * @ORM\ManyToOne(targetEntity=Image::class, inversedBy="indexations")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $image;

    /**
     * @ORM\ManyToOne(targetEntity=Page::class, inversedBy="indexations")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $page;

    /**
     * @ORM\ManyToOne(targetEntity=Dci::class, inversedBy="indexations")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $dci;

    /**
     * @var int
     *
     * @ORM\Column(name="occurrence", type="integer", nullable=false)
     */
    protected $occurrence = 1;

    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
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

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

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

    public function getDci(): ?Dci
    {
        return $this->dci;
    }

    public function setDci(?Dci $dci): self
    {
        $this->dci = $dci;

        return $this;
    }

    public function setOccurrence(int $occurrence): self
    {
        $this->occurrence = $occurrence;

        return $this;
    }

    public function getOccurrence(): int
    {
        return $this->occurrence;
    }    
}
