<?php

namespace App\Entity\UserManagement;

use App\Entity\LovManagement\TypeLien;
use App\Repository\UserManagement\LienRepository;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use DH\DoctrineAuditBundle\Annotation as Audit;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=LienRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Audit\Auditable
 * @Audit\Security(view={"ROLE_AUDIT"})
 */
class Lien
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
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity=TypeLien::class)
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $typeLien;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="liens")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $client;

    public function __toString()
    {
        return $this->url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTypeLien(): ?TypeLien
    {
        return $this->typeLien;
    }

    public function setTypeLien(?TypeLien $typeLien): self
    {
        $this->typeLien = $typeLien;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }
}
