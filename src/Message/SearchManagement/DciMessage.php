<?php

namespace App\Message\SearchManagement;

use App\Entity\SearchManagement\Dci;

final class DciMessage
{
    private $dci;

    public function __construct(Dci $dci)
    {
        $this->dci = $dci;
    }

    public function getDci(): Dci
    {
        return $this->dci;
    }
}
