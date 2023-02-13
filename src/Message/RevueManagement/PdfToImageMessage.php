<?php

namespace App\Message\RevueManagement;

use App\Entity\RevueManagement\Numero;

class PdfToImageMessage
{
    private $numero;

    public function __construct(Numero $numero)
    {
        $this->numero = $numero;
    }

    public function getNumero(): Numero
    {
        return $this->numero;
    }
}
