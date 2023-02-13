<?php

namespace App\Entity\LovManagement;

use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\JourRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Jour extends Lov
{
    public const LUNDI = 'lun';
    public const MARDI = 'mar';
    public const MERCREDI = 'mer';
    public const JEUDI = 'jeu'; 
    public const VENDREDI = 'ven'; 
    public const SAMEDI = 'sam'; 

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
