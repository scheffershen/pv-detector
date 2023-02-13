<?php

namespace App\Message\RevueManagement;

use App\Entity\RevueManagement\Numero;

class PdfToImageUpdateMessage
{
    private $numero;
    private $old_numero;

    public function __construct(string $old_numero, Numero $numero)
    {
        $this->numero = $numero;
        $this->old_numero = $old_numero;
    }

    public function getNumero(): Numero
    {
        return $this->numero;
    }

    public function getOldNumero(): string
    {
        return $this->old_numero;
    }
}
