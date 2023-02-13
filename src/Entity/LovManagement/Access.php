<?php

namespace App\Entity\LovManagement;

use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\AccessRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Access extends Lov
{
    public const PAPER = 'PAPER';
    public const WEB = 'WEB';
    public const WEB_PAPER = 'WEB_PAPER';
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
