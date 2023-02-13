<?php

namespace App\Tests\Utils;

use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Utils
 * @group Utils
 */
class UtilsTest extends TestCase
{
    // Tests no accented charaters
    public function testNoAccent(): void
    {
        $this->assertEquals('aeec', Utils::no_accent('àéèç'));
    }

    // Tests remove accents
    public function testRemoveAccent(): void
    {
        $this->assertEquals('aeec', Utils::remove_accents('àéèç'));
    }

    // Tests full text search
    public function testFullTextSearch(): void
    {
        $this->assertFalse(Utils::full_text_search('abc ', 'abcdefghijklmnopqrstuvwxyz'));
    }

    // Tests word count
    public function testWordCount(): void
    {
        $this->assertEquals(3, Utils::word_count('abc abc', 'abc'));
    }

    // Tests word search
    public function testWordSearch(): void
    {
        $this->assertTrue(Utils::word_search('se référer au site de la HAS', 'fer|FER|fér'));
    }

    // Tests number of words
    public function testNumberOfWords(): void
    {
        $this->assertEquals(3, Utils::number_of_words('abc', 'abc abc abc'));
    }
}
