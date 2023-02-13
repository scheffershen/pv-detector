<?php

namespace App\Entity\RevueManagement;

use App\Entity\RapportManagement\Rapport;
use App\Entity\SearchManagement\Indexation;
use App\Repository\RevueManagement\NumeroRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DatetimeInterface;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=NumeroRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 * @Vich\Uploadable
 */
class Numero
{
    public const SUBMITTED = "submitted";
    public const TREATMENT = "treatment";  
    public const REJECTED = "rejected";
    public const PUBLISHED = "published";
    public const CONTROLLED =  "controlled";

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
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"detail", "list"})
     */
    private $numero;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"detail", "list"})
     */
    private $receiptDate;

    /**
     * @ORM\ManyToOne(targetEntity=Revue::class, inversedBy="numeros")
     * @ORM\JoinColumn(nullable=false)
     */
    private $revue;

    /**
     * @Vich\UploadableField(mapping="uploads_file_revue", fileNameProperty="fileUri")
     *
     * @var File
     */
    protected $file;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"detail", "list"})
     */
    private $fileUri;

    /**
     * @ORM\OneToMany(targetEntity=Page::class, mappedBy="numero", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"numeroPage" = "ASC"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $pages;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"detail", "list"})
     */
    private $isImage = false;

    /**
     * @ORM\OneToMany(targetEntity=Image::class, mappedBy="numero", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"numeroPage" = "ASC"})
     * @Groups({"detail", "list"})
     * @MaxDepth(2)
     */
    private $images;
    protected $imagesZip;
    /**
     * @ORM\Column(type="string", length=255, options={"default": "submitted"})
     */
    private $state = 'submitted';

    /**
     * @ORM\Column(type="boolean")
     */
    private $isIndexed;

    /**
     * @ORM\OneToMany(targetEntity=Indexation::class, mappedBy="numero", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $indexations;

    /**
     * @ORM\OneToMany(targetEntity=Rapport::class, mappedBy="numero", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $rapports;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->indexations = new ArrayCollection();
        $this->isIndexed = false;
        $this->rapports = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }
    
    public function getReceiptDate(): ?DatetimeInterface
    {
        return $this->receiptDate;
    }

    public function setReceiptDate(DatetimeInterface $receiptDate): self
    {
        $this->receiptDate = $receiptDate;

        return $this;
    }

    public function getRevue(): ?Revue
    {
        return $this->revue;
    }

    public function setRevue(?Revue $revue): self
    {
        $this->revue = $revue;

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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file = null): self
    {
        $this->file = $file;

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
            $page->setNumero($this);
        }

        return $this;
    }

    public function removePage(Page $page): self
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
            // set the owning side to null (unless already changed)
            if ($page->getNumero() === $this) {
                $page->setNumero(null);
            }
        }

        return $this;
    }

    public function getIsImage(): ?bool
    {
        return $this->isImage;
    }

    public function setIsImage(?bool $isImage): self
    {
        $this->isImage = $isImage;

        return $this;
    }

    public function isImage(): bool
    {
        return $this->getIsImage();
    }

    public function isPdf(): bool
    {
        return !$this->getIsImage();
    }

    public function getIsPdf(): ?bool
    {
        return $this->IsPdf();
    }

    /**
     * @return Collection|Image[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setNumero($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getNumero() === $this) {
                $image->setNumero(null);
            }
        }

        return $this;
    }

    public function getImagesZip()
    {
        return $this->imagesZip;
    }

    public function setImagesZip(File $ImagesZip = null)
    {
        $this->ImagesZip = $ImagesZip;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getNbDcis(): int
    {
        $nbDcis = 0;
        if ($this->getIsImage()) {
            foreach ($this->getImages() as $image) {
                foreach ($image->getPages() as $page) {
                    if (!$page->getIsDeleted()) {
                        $nbDcis += \count($page->getDcis());
                    }
                }
            }
        } else {
            foreach ($this->getPages() as $page) {
                if (!$page->getIsDeleted()) {
                    $nbDcis += \count($page->getDcis());
                }
            }
        }

        return $nbDcis;
    }

    public function getDcis(): Collection
    {
        $dcis = new ArrayCollection();
        if ($this->getIsImage()) {
            foreach ($this->getImages() as $image) {
                foreach ($image->getPages() as $page) {
                    if (!$page->getIsDeleted()) {
                        foreach ($page->getDcis() as $dci) {
                            if ($dci->getIsValid()) {
                                if (!$dcis->contains($dci)) {
                                    $dcis[] = $dci;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($this->getPages() as $page) {
                if (!$page->getIsDeleted()) {
                    foreach ($page->getDcis() as $dci) {
                        if ($dci->getIsValid()) {
                            if (!$dcis->contains($dci)) {
                                $dcis[] = $dci;
                            }
                        }
                    }
                }
            }
        }

        return $dcis;
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
            $indexation->setNumero($this);
        }

        return $this;
    }

    public function removeIndexation(Indexation $indexation): self
    {
        if ($this->indexations->removeElement($indexation)) {
            // set the owning side to null (unless already changed)
            if ($indexation->getNumero() === $this) {
                $indexation->setNumero(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Rapport[]
     */
    public function getRapports(): Collection
    {
        return $this->rapports;
    }

    public function addRapport(Rapport $rapport): self
    {
        if (!$this->rapports->contains($rapport)) {
            $this->rapports[] = $rapport;
            $rapport->setNumero($this);
        }

        return $this;
    }

    public function removeRapport(Rapport $rapport): self
    {
        if ($this->rapports->removeElement($rapport)) {
            // set the owning side to null (unless already changed)
            if ($rapport->getNumero() === $this) {
                $rapport->setNumero(null);
            }
        }

        return $this;
    }

    public function hasRapport(): ?Rapport
    {
        foreach ($this->rapports as $rapport) {
            return $rapport;
        }
        return null; 
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
}
