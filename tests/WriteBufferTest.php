<?php

namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
use Postgres\WriteBuffer;

class WriteBufferTest extends TestCase
{
    public function testCastsToStringAndEndsInNUL()
    {
        $msg = new WriteBuffer();
        $this->assertEquals("", "{$msg}");
    }

    public function testWritingMessageIdentifier()
    {
        $msg = new WriteBuffer();
        $msg->writeIdent('Q');
        $this->assertEquals("Q", "{$msg}");
    }

    public function testWritingNUL()
    {
        $msg = new WriteBuffer();
        $msg->writeNUL();
        $this->assertEquals("\0", "{$msg}");
    }

    public function testWritingInt32()
    {
        $msg = new WriteBuffer();
        $msg->writeInt32(2147483647);
        $this->assertTrue(strlen($msg) === 4);
        $arr = unpack('Nint', $msg);
        $this->assertEquals(2147483647, $arr['int']);
    }

    public function testWritingInt16()
    {
        $msg = new WriteBuffer();
        $msg->writeInt16(3);
        $this->assertTrue(strlen($msg) === 2);
        $arr = unpack('nint', $msg);
        $this->assertEquals(3, $arr['int']);
    }

    public function testWritingString()
    {
        $msg = new WriteBuffer();
        $msg->writeString("SELECT 1");
        $this->assertEquals("SELECT 1\0", "{$msg}");
    }

    public function testWritingStartupMessage()
    {
        $msg = new WriteBuffer();
        $msg->writeInt16(3);                // 2
        $msg->writeInt16(0);                // 2
        $msg->writeString('user');          // 5
        $msg->writeString('zacharyrankin'); // 14
        $msg->writeString('database');      // 9
        $msg->writeString('postgres');      // 9
        $msg->writeNUL();                   // 1
        $this->assertEquals(42, strlen($msg));
    }
}
