<?php

namespace App\Tests\Utils;

use App\Utils\WordSearch;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\WordSearch
 * @group Utils
 * vendor/bin/phpunit tests/Utils/WordSearchTest.php
 */
class WordSearchTest extends TestCase
{
    // Tests clean_text()
    public function testCleanText(): void
    {
        $this->assertEquals('se referer au site de la has', WordSearch::clean_text('se référer au site de la HAS', ['ACCENT', 'MINUSCULE']));
    }

    // Tests get_words 
    public function testGetWords(): void
    {
        $this->assertEquals(['fer'], WordSearch::get_words('fer|FER|fér'));
    }

    // Tests word search
    public function testWordSearch(): void
    {
        $this->assertFalse(WordSearch::find_word('se référer au site de la HAS', 'fer|FER|fér'));
        $this->assertTrue(WordSearch::find_word('se référer FeR ite de la HAS', 'fer|FER|fér'));
    }

    // Tests count of exact words
    public function testCountExactWords(): void
    {
        $this->assertEquals(1, WordSearch::count_exact_words('se référer FeR ite de la HAS', 'fer|FER|fér'));
    }

    // Tests highlight_words
    public function testHighlightWords(): void
    {
        $this->assertEquals('se référer <span style="background:#5fc9f6">FER</span> ite de la HAS', WordSearch::highlight_words('se référer FER ite de la HAS', 'fer|FER|fér'));
    }
}
