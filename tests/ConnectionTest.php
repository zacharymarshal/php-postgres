<?php
namespace Postgres\Tests;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConnect()
    {
        $conn = \Postgres\connect('tcp://localhost:5432');
        \Postgres\disconnect($conn);
        $this->assertNotEmpty($conn);
    }

    public function testSend()
    {
        $conn = \Postgres\connect('tcp://localhost:5432');
        \Postgres\send($conn, '3::int16 0::int16' .
            ' user\0zacharyrankin\0database\0dev_template\0\0');
        \Postgres\disconnect($conn);
        $this->assertTrue(true);
    }
    
    public function testGet()
    {
        $conn = \Postgres\connect('tcp://localhost:5432');
        \Postgres\send($conn, '3::int16 0::int16' .
            ' user\0zacharyrankin\0database\0dev_template\0\0');
        $result = \Postgres\get($conn);
        var_dump($result);
        \Postgres\send($conn, 'BEGIN\0', 'Q');
        $result = \Postgres\get($conn);
        var_dump($result);
        exit;
    }
}
