<?php
namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
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
                'Q::ident LENGTH "SELECT 1"',
                pack('aNZ*', 'Q', (4 + 8 + 1), "SELECT 1")
            ],
            [
                'LENGTH 3::int16 0::int16 NUL',
                pack('Nnnx', (4 + 4 + 1), 3, 0)
            ],
            [
                'LENGTH 3::int16 0::int16 "user" "postgres" "database" "postgres" NUL',
                pack('NnnZ*Z*Z*Z*x', (4 + 36 + 1), 3, 0, "user", "postgres", "database", "postgres")
            ],
            [
                'Q::ident LENGTH "SELECT 1 FROM "table""::string',
                pack('aNZ*', 'Q', (4 + 21 + 1), 'SELECT 1 FROM "table"')
            ],
            [
                'Q::ident 13::int32 "SELECT 1"::string',
                pack('aNZ*', 'Q', (4 + 8 + 1), "SELECT 1")
            ]
        ];
    }
}
