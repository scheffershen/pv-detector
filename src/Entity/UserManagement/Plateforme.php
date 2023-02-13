<?php

namespace App\Entity\UserManagement;

use App\Repository\UserManagement\PlateformeRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=PlateformeRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 * @Vich\Uploadable 
 */
class Plateforme
{
    use ActorTrait;
    use DateTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * maybe late.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
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
     * @ORM\Column(type="text")
     */
    private $version;

    /**
     * @ORM\Column(type="text")
     */
    private $poweredBy;

    public function __toString()
    {
        return $this->nom;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

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
    
    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getPoweredBy(): ?string
    {
        return $this->poweredBy;
    }

    public function setPoweredBy(string $poweredBy): self
    {
        $this->poweredBy = $poweredBy;

        return $this;
    }
}
