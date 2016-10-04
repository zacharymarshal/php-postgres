<?php

namespace Postgres\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Postgres\ReadBuffer;

class ReadBufferTest extends TestCase
{
    public function testReadingInt32()
    {
        $msg = new ReadBuffer(pack('N', 3));
        $this->assertEquals(3, $msg->readInt32());
    }

    public function testReadingMultipleIntegers()
    {
        $msg = new ReadBuffer(pack('N', 3) . pack('N', 45));
        $this->assertEquals(3, $msg->readInt32());
        $this->assertEquals(45, $msg->readInt32());
    }

    public function testReadingString()
    {
        $msg = new ReadBuffer("hello\0");
        $this->assertEquals("hello", $msg->readString());
    }

    public function testReadingMultipleStrings()
    {
        $msg = new ReadBuffer("hello\0world\0");
        $this->assertEquals("hello", $msg->readString());
        $this->assertEquals("world", $msg->readString());
    }

    public function testReadingInvalidString()
    {
        $this->expectException(Exception::class);

        $msg = new ReadBuffer("hello");
        $msg->readString();
    }

    public function testReadingByte()
    {
        $msg = new ReadBuffer('I');
        $this->assertEquals('I', $msg->readByte());
    }
    
    public function testCastingToString()
    {
        $msg = new ReadBuffer('hello');
        $this->assertEquals('hello', "{$msg}");
    }
}
