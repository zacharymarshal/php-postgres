<?php
namespace Postgres\Tests;

class CreateMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider tokenizeMessageProvider
     */
    public function testTokenizeMessage($msg, $expected)
    {
        $tokens = \Postgres\tokenizeMessage($msg);
        $this->assertEquals($expected, $tokens);
    }

    public function tokenizeMessageProvider()
    {
        return [
            [
                "Hello World",
                [
                    ['type' => 'unknown', 'value' => 'Hello World']
                ]
            ],
            [
                "3::int16",
                [
                    ['type' => 'int16', 'value' => '3::int16', 'number' => '3']
                ]
            ],
            [
                "3::int16 6::int16",
                [
                    ['type' => 'int16', 'value' => '3::int16', 'number' => '3'],
                    ['type' => 'whitespace', 'value' => ' '],
                    ['type' => 'int16', 'value' => '6::int16', 'number' => '6'],
                ]
            ],
            [
                "1::int16  1::int16",
                [
                    ['type' => 'int16', 'value' => '1::int16', 'number' => '1'],
                    ['type' => 'whitespace', 'value' => '  '],
                    ['type' => 'int16', 'value' => '1::int16', 'number' => '1'],
                ]
            ],
        ];
    }
}
