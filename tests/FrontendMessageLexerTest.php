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
            [
                "3::int16",
                ['type' => 'int16', 'value' => 3]
            ],
            [
                "99::int16",
                ['type' => 'int16', 'value' => 99]
            ],
            [
                "21::int32",
                ['type' => 'int32', 'value' => 21]
            ],
            [
                "0::int32",
                ['type' => 'int32', 'value' => 0]
            ],
            [
                '"user"',
                ['type' => 'string', 'value' => "user"]
            ],
            [
                '"SELECT * FROM "table" LIMIT 1;"::string',
                ['type' => 'string', 'value' => 'SELECT * FROM "table" LIMIT 1;']
            ],
            [
                "NUL",
                ['type' => 'constant', 'value' => 'NUL']
            ],
            [
                "LENGTH",
                ['type' => 'constant', 'value' => 'LENGTH']
            ],
            [
                "Q::ident",
                ['type' => 'ident', 'value' => 'Q']
            ],
            [
                "p::ident",
                ['type' => 'ident', 'value' => 'p']
            ],
        ];
    }

    public function testGettingMultipleTokens()
    {
        $lexr = new FrontendMessageLexer('Q::ident LENGTH "SELECT 1" NUL');
        $this->assertEquals(['type' => 'ident', 'value' => 'Q'], $lexr->nextToken());
        $this->assertEquals(['type' => 'whitespace', 'value' => ' '], $lexr->nextToken());
        $this->assertEquals(['type' => 'constant', 'value' => 'LENGTH'], $lexr->nextToken());
        $this->assertEquals(['type' => 'whitespace', 'value' => ' '], $lexr->nextToken());
        $this->assertEquals(['type' => 'string', 'value' => 'SELECT 1'], $lexr->nextToken());
        $this->assertEquals(['type' => 'whitespace', 'value' => ' '], $lexr->nextToken());
        $this->assertEquals(['type' => 'constant', 'value' => 'NUL'], $lexr->nextToken());
    }
}
