<?php

namespace App\Entity\RapportManagement;

use App\Entity\RevueManagement\Numero;
use App\Repository\RapportManagement\RapportRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RapportRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Rapport
{
    public const WORD = 'word';
    public const PDF = 'pdf';
    public const HTML = 'html'; 
    public const VALIDATOR = 'validator';
        
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
     * @ORM\Column(type="text")
     */
    private $commentaire;

    /**
     * @ORM\ManyToOne(targetEntity=Numero::class, inversedBy="rapports")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $numero;

    /**
     * @ORM\OneToOne(targetEntity=ControleQualite::class, mappedBy="rapport", cascade={"persist", "remove"})
     */
    private $controleQualite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): self
    {
        $this->commentaire = $commentaire;

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

    public function getControleQualite(): ?ControleQualite
    {
        return $this->controleQualite;
    }

    public function setControleQualite(ControleQualite $controleQualite): self
    {
        // set the owning side of the relation if necessary
        if ($controleQualite->getRapport() !== $this) {
            $controleQualite->setRapport($this);
        }

        $this->controleQualite = $controleQualite;

        return $this;
    }
}
