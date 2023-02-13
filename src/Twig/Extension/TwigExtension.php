<?php

namespace App\Twig\Extension;

use App\Entity\UserManagement\User;
use App\Entity\RapportManagement\Numero;
use App\Repository\SearchManagement\IndexationRepository;
use App\Repository\SearchManagement\DciRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function Rap2hpoutre\RemoveStopWords\remove_stop_words;

class TwigExtension extends AbstractExtension
{
    private $indexationRepository;
    private $dciRepository;

    public function __construct(IndexationRepository $indexationRepository, DciRepository $dciRepository)
    {
        $this->indexationRepository = $indexationRepository;
        $this->dciRepository = $dciRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', 'json_decode'),
            new TwigFilter('mb_convert_encoding', 'mb_convert_encoding'),
            new TwigFilter('isImage', [$this, 'isImage']),
            new TwigFilter('isPdf', [$this, 'isPdf']),
            new TwigFilter('imagePageNumber', [$this, 'imagePageNumber']),
            new TwigFilter('nbDciIndexation', [$this, 'nbDciIndexation']),
            new TwigFilter('nbNumeroIndexation', [$this, 'nbNumeroIndexation']),
            new TwigFilter('nbClientIndexation', [$this, 'nbClientIndexation']),
            new TwigFilter('nbClientsUser', [$this, 'nbClientsByUser']),
            //new TwigFilter('dciHightlight', [$this, 'dciHightlight']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('keyHightlight', [$this, 'keyHightlight']),
            new TwigFunction('dcisHightlight', [$this, 'dcisHightlight']),
        ];
    }

    // version actu pr souligner les mots, unitaire test à faire !!!
    // highlight one dci
    public function keyHightlight(string $text, string $key): string
    {
        $split_words = \explode("|", $key);
        $patterns = array();
        $replacements = array();

        foreach(array_unique($split_words) as $word)
        {
            if (!empty($word)) {
                $patterns[] = '/'.trim($word).'/';
                $replacements[] = '<span class="bg-warning">'.trim($word).'</span>';    
            }
        }

        return "<div class='d-line'>".\preg_replace($patterns, $replacements, $text)."</div>";  
         
    }

    /**
     * highlight all dcis
    */
    public function dcisHightlight(string $text, $clients = null): string
    {
        if ($clients === null) {
            $dcis = $this->dciRepository->findBy(['isValid' => true], ['updateDate' => 'ASC']);
        } else {
            $dcis = $this->dciRepository->getDcisByClients($clients);
        }
        
        foreach($dcis as $dci) {
            $split_words = \explode("|", $dci->getTitle());

            foreach(array_unique($split_words) as $word)
            {
                if (!empty($word)) {
                    $word = \str_replace("/","\/", $word);
                    $patterns[] = "/\b".$word."\b/";
                    $replacements[] = '<span class="bg-warning">'.$word.'</span>';   
                }
            }
        }
        return "<div class='d-line'>".\preg_replace(array_unique($patterns), array_unique($replacements), $text)."</div>";
    }

    private function highlight($text, $words): string
    {      
        \preg_match_all('~\w+~', $words, $m);
        if(!$m)
            return $text;
        $re = '~\\b(' . \implode('|', $m[0]) . ')\\b~';
        return \preg_replace($re, '<span class="text-danger">$0</span>', $text);
    }

    private function highlighter_text($text, $words): string
    {
        $split_words = \explode(" ", $words);
        foreach($split_words as $word)
        {
            if (!empty($word)) {
                $text = \preg_replace("|($word)|Ui" ,
                    "<span class=\"text-danger\">$1</span>" , $text );     
            }
        }
        return $text;
    }

    /**
     * twig filter with multiple parameters
    */
    public function nbClientsByUser(array $clients, User $user): int
    {
        $nb_client = 0;
        foreach ($clients as $client) {
            if ( $user->getClientsResponsable()->contains($client) || $user->getClientsBackupResponsable()->contains($client) ) {
                $nb_client++;
            }
        }
        return $nb_client;
    }

    public function isImage(?string $string): bool
    {
        $supported_image = ['gif', 'jpg', 'jpeg', 'png'];

        $ext = strtolower(pathinfo($string, PATHINFO_EXTENSION));
        if (\in_array($ext, $supported_image)) {
            return true;
        } else {
            return false;
        }
    }

    public function isPdf(?string $string): bool
    {
        $supported = ['pdf'];

        $ext = strtolower(pathinfo($string, PATHINFO_EXTENSION));
        if (\in_array($ext, $supported)) {
            return true;
        } else {
            return false;
        }
    }

    public function imagePageNumber(?string $string): string
    {
        $ext = explode('-', $string);

        return $ext[1];
    }

    public function nbDciIndexation(int $dci): string
    {
        return $this->indexationRepository->getTotalByDci($dci);
    }

    public function nbNumeroIndexation(int $numero): string
    {
        return $this->indexationRepository->getTotalByNumero($numero);
    }

    public function nbClientIndexation(int $client): string
    {
        return $this->indexationRepository->getTotalByClient($client);
    }
}
