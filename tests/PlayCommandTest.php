<?php

namespace Postgres\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Postgres\ConnectionInterface;
use Postgres\PlayCommand;

class PlayCommandTest extends TestCase
{
    public function testRunCommand()
    {
        $stub_conn = $this->createMock(ConnectionInterface::class);
        $play_cmd = new PlayCommand();
        $this->assertTrue($play_cmd->run($stub_conn, '', []));
    }

    public function testConnects()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('connect');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', []);
    }

    public function testStartingUp()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('startup');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', []);
    }

    public function testStartupOption()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->never())
            ->method('startup');

        $play_cmd = new PlayCommand();
        $play_cmd->run($conn, '', [
            'startup' => false
        ]);
    }

    public function testWriteCommand()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Q::ident LENGTH "SELECT 1"::string NUL'));

        $play_cmd = new PlayCommand();
        $input = <<<'IN'
write Q::ident LENGTH "SELECT 1"::string NUL
IN;
        $play_cmd->run($conn, $input);
    }

    public function testMultipleWriteCommands()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [$this->equalTo('Q::ident LENGTH "SELECT 1"::string NUL')],
                [$this->equalTo('Q::ident LENGTH "SELECT 2"::string NUL')]
            );

        $play_cmd = new PlayCommand();
        $input = <<<'IN'
write Q::ident LENGTH "SELECT 1"::string NUL
write Q::ident LENGTH "SELECT 2"::string NUL
IN;
        $play_cmd->run($conn, $input);
    }
    
    public function testRunFailsBadCommand()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid command");
        $play_cmd = new PlayCommand();
        $conn = $this->createMock(ConnectionInterface::class);
        $play_cmd->run($conn, '... fail');
    }

    public function testRunNoInput()
    {
        $play_cmd = new PlayCommand();
        $conn = $this->createMock(ConnectionInterface::class);
        $this->assertTrue($play_cmd->run($conn, ""));
        $this->assertTrue($play_cmd->run($conn, "\n\n"));
    }
}
