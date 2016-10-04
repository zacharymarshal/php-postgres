<?php

namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Warning;
use Postgres\Connection;
use Postgres\PostgresException;

class ConnectionTest extends TestCase
{
    public function testFailureConnecting()
    {
        $this->expectException(PostgresException::class);
        $conn = new Connection("tcp://fail:2222");
        $org_warn = PHPUnit_Framework_Error_Warning::$enabled;
        PHPUnit_Framework_Error_Warning::$enabled = false;
        $conn->connect();
        PHPUnit_Framework_Error_Warning::$enabled = $org_warn;
    }

    public function testSuccessfulConnection()
    {
        $conn = new Connection("tcp://localhost:5432");
        $conn->connect();
    }
}
