<?php

namespace App\Tests\Utils;

use App\Utils\WordSearch;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\WordSearch
 * @group Utils
 * ./vendor/bin/phpunit tests/Utils/WordSearchTest2.php
 */
class WordSearchTest2 extends TestCase
{

    // Tests count of exact words
    public function testCountExactWords(): void
    {
        var_dump(WordSearch::get_unique_words('omega 3|oméga 3|Omega-3|oméga-3|oméga|Omega|omega|OmegA'));
        $text = "( Dossier
5 La vitamine D et les acides gras oméga 3 dans la prévention des pathologies auto - immunes
Results : Vitamin D Supplementation over 5 years
Confirmed Incident Autoimmune Diseases , n = 278
Confirmed & Probable Incident Autoimmune Diseases , n = 457
HR 0.78 ( 95 % CI 0.61 , 1.00 ) p0.045
005
HR0,85 ( 95 % CI 0.71 1.02 ) pe 0.09
2
0
Plasco 1294 Active 12977
Follow - up years Number at sk 12525 12736
12851
12319 12336
12488
Number at risk 12717 12547
Placebo 12944
12486 12829
12387 12401
Active 12927
1229 12252
Results : n - 3 Fatty Aid Supplementation over 5 years
La contribution des facteurs ali mentaires à la physiopathologie des maladies auto - immunes ( MAI ) est connue . La vitamine D est associée à une réduction du risque de certaines MAI et les acides gras oméga 3 ont des propriétés anti - inflammatoires . Il existe cependant peu d'études d'intervention . L'étude VITAL ( VI Tamin Dand Omega - 3 Trial ) est un essai de grande envergure qui vise à analyser les effets d'une supplé mentation en vitamine D et d'acide gras oméga 3 d'origine marine chez des sujets de la population géné rale . Cette étude ancillaire de VITAL analyse spécifiquement l'incidence de cette intervention sur le risque de MAI . Il s'agit d'un essai rando misé en double aveugle contrôlé versus placebo qui a été réalisé chez des sujets masculins de plus de 50 ans et des femmes de plus de 55 ans aux États - Unis , avec un schéma selon un plan factoriel . Les patients recevaient de la vitamine D ( VitD ) ( 2 000 UI / jour ) , des acides gras oméga 3 ( AG3 ) ( 1 000 mg / j ) ou un placebo ( PBO ) . Il y avait quatre bras de combinaison avec la vitamine D ,
Confirmed Incident Autoimmune Diseases , n = 278
Confirmed & Probable Incident Autoimmune Diseases , n = 457
0.0021 Alive
6.620
0.000
HR 0.85 ( 95 % CI0.67 1.09 ) , p = 0.020
006
HR 0.82 ( 95 % CI0.62 0.99 ) , p = 0.04
02
Paco 12918 Active 12953
Follow - up years Number at ik 12753 12616 12746 12625
12822 12852
1246 12491
Follow up years Number at AS 12696 125 12711 12588
12326 12349
Placebo 12938 Active 12953
12835 12840
12371 1247
12229 12272
Figure 5 Incidence de maladies auto - immunes à 5 ans dans les bras placebo et vitamine D et les bras placebo et acide gras oméga 3 . Résultats de l'essai VITAL ( d'après Costenbader K , 0957 ) .
Tableau 2 - Incidence de polyarthrite rhumatoïde ( cas confirmés et cas probables ) selon la prise de vitamine D , acide gras oméga ou placebo dans l'essai VITAL ( d'après Costenbader K , 0957 ) .
Bras vitamine D
Bras placebo
HR ( IC 95 % )
P
PR confirmée
14
24
0,58 ( 0,3-1,13 )
0,11
PR probable et confirmée
17
27
0,63 ( 0,34-1,16 )
1,14
Bras oméga 3
Bras placebo
14
24
0,58 ( 0,3-1,13 )
0,11
PR confirmée PR probable et confirmée
16
28
0,57 ( 0,31-1,06 )
0,07
450
RHUMATOS - DÉCEMBRE 2021. VOL . 18. NUMÉRO 169";

        $this->assertEquals(7, WordSearch::count_exact_words($text, 'omega 3|oméga 3|Omega-3|oméga-3|oméga|Omega|omega|OmegA'));
    }

}
