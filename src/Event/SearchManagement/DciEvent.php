<?php

namespace App\Event\SearchManagement;

use App\Entity\SearchManagement\Dci;
use Symfony\Contracts\EventDispatcher\Event;

class DciEvent extends Event
{
    public const DESINDEXER = 'dci.desindexer';

    /**
     * @var Dci
     */
    protected $dci;

    /**
     * DciEvent constructor.
     * @param Dci $session
     */
    public function __construct(Dci $dci)
    {
        $this->dci = $dci;
    }

    /**
     * @return Dci
     */
    public function getDci()
    {
        return $this->dci;
    }
}