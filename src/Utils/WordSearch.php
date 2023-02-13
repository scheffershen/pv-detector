<?php

namespace App\Utils;

//https://www.zendevs.xyz/les-expressions-regulieres-en-php-regex/

final class WordSearch
{

    /**
     * clean the word
    */
    public static function clean_text(string $str, $options = []): string
    {
        if (\in_array('TOUT', $options)) {
            $options = ['HTML', 'TRIM', 'MAJUSCULE', 'MINUSCULE', 'ACCENT', 'PONCTUATION', 'TABULATION', 'ENTER', 'DOUBLE'];
        }
        foreach ($options as $option) {
            switch ($option) {
                // Suppression des espaces vides en debut et fin de chaque ligne
                case 'TRIM':
                    $str = preg_replace("#^[\t\f\v ]+|[\t\f\v ]+$#m",'',$str);
                break;

                // Remplacement des caractères accentués par leurs équivalents non accentués
                case 'ACCENT':
                    $str = htmlentities($str, ENT_NOQUOTES, 'utf-8');
                    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
                    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. 'œ'
                    $str = html_entity_decode($str);
                break;

                // Transforme tout le texte en minuscule
                case 'MINUSCULE':
                    $str = mb_strtolower($str, 'UTF-8');
                break;

                // Transforme tout le texte en majuscule
                case 'MAJUSCULE':
                    $str = mb_strtoupper($str, 'UTF-8');
                break;

                // Remplace toute la ponctuation par des espaces
                case 'PONCTUATION':
                    $str = preg_replace('#([[:punct:]])#',' ',$str);
                    $exceptions = array("’");
                    $str = str_replace($exceptions,' ',$str);
                break;

                // Remplace les tabulations par des espaces
                case 'TABULATION':
                    $str = preg_replace("#\h#u", ' ', $str);
                break;

                // Remplace les espaces multiples par des espaces simples
                case 'DOUBLE':
                    $str = preg_replace('#[" "]{2,}#', ' ', $str);
                break;

                // Remplace 1 entrée (\r\n) par 1 espace
                case 'ENTER':
                    $str = str_replace(["\r", "\n"], ' ', $str);
                break;
                // Supprime toutes les balises html
                case 'HTML':
                    $str = strip_tags($str);
                break;
            }
        }

        return $str;
    }

    /*
    * list of words 
    */
    public static function get_words(string $text): array
    {
        $words = explode("|", $text);

        $mots_noaccent = array_map(function($word) {
            return self::clean_text($word, ['ACCENT', 'MINUSCULE']);
        }, $words);

        return array_unique($mots_noaccent);
    }

    /**
     * get unqiue word in a array
     * @param array $words array of words
     */
    public static function get_unique_words(string $text): array
    {
        $words = explode("|", $text);
        $unique_words = [];

        foreach ($words as $word) {
            if (strpos($word, "-") !== false) {
                $_words = explode("-", $word);
            } else {
                $_words = explode(" ", $word);
            }

            array_push($unique_words, $_words[0]);
        }

        $mots_noaccent = array_map(function($word) {
            return self::clean_text($word, ['ACCENT', 'MINUSCULE']);
        }, $unique_words);

        return array_unique($mots_noaccent);
    }

    /*
    * if the word in the content
    * https://regex101.com/
    */
    public static function find_word($text, $words) {
        $mots = self::get_words($words);

        foreach (array_unique($mots) as $mot) { 
            // \b can be used in regex to match word boundaries.
            $mot = str_replace("/", "\/", $mot); // escape the / character, Warning: preg_match(): Unknown modifier 'c'
            $pattern = "/\b".$mot."\b/i";
            if (preg_match($pattern, self::clean_text($text, ['ACCENT', 'MINUSCULE']))) return true; 
        }

        return false;
    }

    /*
    * Count the exact words in the content
    */
    public static function count_exact_words($text, $words) {
        $mots = self::get_unique_words($words);

        $nb_occurrence = 0;

        foreach ($mots as $mot) { 
            $pattern = "/\b".trim($mot)."\b/i";
            $nb_occurrence += preg_match_all($pattern, self::clean_text($text, ['ACCENT', 'MINUSCULE']));
        }        

        return $nb_occurrence;
    }

    /*
    * Highlight a word in the content
    */
    public static function highlight_words($text, $words) {
        $mots = \explode("|", $words);

        foreach (array_unique($mots) as $mot) { 
            // \b can be used in regex to match word boundaries.
            $pattern = "/\b".trim($mot)."\b/";
            $text = preg_replace($pattern, '<span style="background:#5fc9f6">'.$mot.'</span>', $text);
        }

        return $text;
    }

}