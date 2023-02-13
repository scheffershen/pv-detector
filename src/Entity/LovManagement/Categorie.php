<?php

namespace App\Entity\LovManagement;

use App\Entity\SearchManagement\Dci;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\CategorieRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Categorie extends Lov
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=Dci::class, mappedBy="categorie", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $dcis;

    public function __construct()
    {
        parent::__construct();
        $this->dcis = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
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
            $dci->setCategorie($this);
        }

        return $this;
    }

    public function removeDci(Dci $dci): self
    {
        if ($this->dcis->removeElement($dci)) {
            // set the owning side to null (unless already changed)
            if ($dci->getCategorie() === $this) {
                $dci->setCategorie(null);
            }
        }

        return $this;
    }
}
