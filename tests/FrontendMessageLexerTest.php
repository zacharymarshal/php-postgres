<?php

namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
use Postgres\FrontendMessageLexer;

class FrontendMessageLexerTest extends TestCase
{
    public function testTakesAString()
    {
        new FrontendMessageLexer('3::int32');
        $this->assertTrue(true);
    }

    /**
     * @dataProvider nextTokenProvider
     */
    public function testTokenizeMessage($msg, $expected)
    {
        $lexr = new FrontendMessageLexer($msg);
        $this->assertEquals($expected, $lexr->nextToken());
    }

    public function nextTokenProvider()
    {
        return [
            [
                "",
                []
            ],
            [
                "hello",
                ['type' => 'unknown', 'value' => "hello"]
            ],
            [
                " ",
                ['type' => 'whitespace', 'value' => " "]
            ],
            [
                "   ",
                ['type' => 'whitespace', 'value' => "   "]
            ],
        ];
    }
}
