<?php

namespace App\Entity\RevueManagement;

use App\Entity\SearchManagement\Indexation;
use App\Repository\RevueManagement\ImageRepository;
use App\Traits\IsDeletedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=ImageRepository::class)
 * @Vich\Uploadable
 */
class Image
{
    use IsDeletedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"detail", "list"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $numeroPage;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"detail", "list"})
     */
    private $fileUri;

    /**
     * @Vich\UploadableField(mapping="uploads_file_revue", fileNameProperty="fileUri")
     *
     * @var File
     */
    protected $upload;

    /**
     * @ORM\OneToMany(targetEntity=Page::class, mappedBy="image", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"numeroPage" = "ASC"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $pages;

    /**
     * @ORM\ManyToOne(targetEntity=Numero::class, inversedBy="images")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $numero;

    /**
     * @ORM\Column(type="integer")
     */
    private $width;

    /**
     * @ORM\Column(type="integer")
     */
    private $height;

    /**
     * @ORM\OneToMany(targetEntity=Indexation::class, mappedBy="image", orphanRemoval=true)
     */
    private $indexations;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->indexations = new ArrayCollection();
    }

    public function __toString()
    {
        if (\is_null($this->fileUri)) {
            return 'NULL';
        }

        return $this->fileUri;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroPage(): ?int
    {
        return $this->numeroPage;
    }

    public function setNumeroPage(?int $numeroPage): self
    {
        $this->numeroPage = $numeroPage;

        return $this;
    }

    public function getFileUri(): ?string
    {
        return $this->fileUri;
    }

    public function setFileUri(?string $fileUri): self
    {
        $this->fileUri = $fileUri;

        return $this;
    }

    // get FileDir && get FileName
    public function getFileDir(): ?string
    {
        $data = explode('/', $this->getFileUri());
        if (\count($data) > 1) {
            return $data[0];
        } else {
            return '';
        }
    }

    public function getFileName(): ?string
    {
        $data = explode('/', $this->getFileUri());
        if (\count($data) > 1) {
            return $data[1];
        } else {
            return $this->getFileUri();
        }
    }

    public function getUpload(): ?File
    {
        return $this->upload;
    }

    public function setUpload(File $upload = null): self
    {
        $this->upload = $upload;

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
            $page->setImage($this);
        }

        return $this;
    }

    public function removePage(Page $page): self
    {
        if ($this->pages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getImage() === $this) {
                $page->setImage(null);
            }
        }

        return $this;
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

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

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
            $indexation->setImage($this);
        }

        return $this;
    }

    public function removeIndexation(Indexation $indexation): self
    {
        if ($this->indexations->removeElement($indexation)) {
            // set the owning side to null (unless already changed)
            if ($indexation->getImage() === $this) {
                $indexation->setImage(null);
            }
        }

        return $this;
    }
}
