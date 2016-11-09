<?php

namespace Postgres\Tests;

use PHPUnit\Framework\TestCase;
use Postgres\Connection;
use Postgres\PlayCommand;

class PlayCommandTest extends TestCase
{
    public function testRunCommand()
    {
        $stub_conn = $this->createMock(Connection::class);
        $play_cmd = new PlayCommand();
        $this->assertTrue($play_cmd->run($stub_conn, '', []));
    }

    public function testConnects()
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('connect');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', []);
    }

    public function testStartingUp()
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('startup');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', []);
    }

    public function testStartupOption()
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->never())
            ->method('startup');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', [
            'startup' => false
        ]);
    }
}
