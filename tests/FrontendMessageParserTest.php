<?php
namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
use Postgres\FrontendMessage;
use Postgres\FrontendMessageParser;

class FrontendMessageParserTest extends TestCase
{
    public function testConstructingTheParser()
    {
        new FrontendMessageParser('Q::ident LENGTH "SELECT 1" NUL');
        $this->assertTrue(true);
    }

    /**
     * @dataProvider getMessageProvider
     */
    public function testGetFrontendMessage($msg, $expected)
    {
        $parser = new FrontendMessageParser($msg);
        $this->assertEquals($expected, "{$parser->getMessage()}");
    }

    public function getMessageProvider()
    {
        return [
            [
                'Q::ident LENGTH "SELECT 1"::string NUL',
                'Q' . pack('N', 13) . "SELECT 1\0"
            ],
            [
                'LENGTH 3::int16 0::int16 NUL',
                pack('N', 9) . pack('n', 3) . pack('n', 0) . "\0"
            ],
            [
                'LENGTH 3::int16 0::int16 "user" NUL "postgres" NUL "database" NUL "postgres" NUL NUL',
                pack('N', 41) . pack('n', 3) . pack('n', 0) . "user\0postgres\0database\0postgres\0\0"
            ],
            [
                'Q::ident LENGTH "SELECT 1"::string NUL',
                'Q' . pack('N', 13) . "SELECT 1\0"
            ]
        ];
    }
}
