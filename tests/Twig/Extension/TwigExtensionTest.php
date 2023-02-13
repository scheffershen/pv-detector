<?php

namespace App\Tests\Twig\Extension;

use App\Twig\Extension\TwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\Extension\TwigExtension
 * vendor/bin/phpunit tests/Twig/Extension/TwigExtensionTest.php
 */
class TwigExtensionTest extends TestCase
{
    protected function getTwigExtensions(): TwigExtension
    {
        return new TwigExtension();
    }

    public function testDcisHightlight(): void
    {
        $functions = ['keyHightlight', 'dcisHightlight'];

        $data = [
            'se référer FER ite de la HAS' => '<div class=\'d-line\'>se référer <span class="bg-warning">FER</span> ite de la HAS'
        ];

        $twigFunctions = $this->getTwigExtensions()->getFunctions();

        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $key => $fuction) {
            if ($key == 'dcisHightlight') {
                foreach ($data as $input => $expected) {
                    $result = $fuction("se référer FER ite de la HAS", null);
                    $this->assertEquals($expected, $result);
                }
            }
        }

    }
}
