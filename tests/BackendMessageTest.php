<?php

namespace Postgres\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Postgres\BackendMessage;

class BackendMessageTest extends TestCase
{
    public function testGettingMessageIdent()
    {
        $msg = new BackendMessage('R', '');
        $this->assertEquals('R', $msg->getIdent());
    }

    public function testReadingInt32()
    {
        $msg = new BackendMessage('R', pack('N', 3));
        $this->assertEquals(3, $msg->readInt32());
    }

    public function testReadingMultipleIntegers()
    {
        $msg = new BackendMessage('R', pack('N', 3) . pack('N', 45));
        $this->assertEquals(3, $msg->readInt32());
        $this->assertEquals(45, $msg->readInt32());
    }

    public function testReadingString()
    {
        $msg = new BackendMessage('R', "hello\0");
        $this->assertEquals("hello", $msg->readString());
    }

    public function testReadingMultipleStrings()
    {
        $msg = new BackendMessage('R', "hello\0world\0");
        $this->assertEquals("hello", $msg->readString());
        $this->assertEquals("world", $msg->readString());
    }

    public function testReadingInvalidString()
    {
        $this->expectException(Exception::class);

        $msg = new BackendMessage('R', "hello");
        $msg->readString();
    }

    public function testReadingByte()
    {
        $msg = new BackendMessage('Z', 'I');
        $this->assertEquals("I", $msg->readByte());
    }
    
    public function testCastingToString()
    {
        $msg = new BackendMessage('Z', 'hello');
        $this->assertEquals('hello', "{$msg}");
    }
}
