<?php

namespace Postgres\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Postgres\ConnectionInterface;
use Postgres\PlayCommand;

class PlayCommandTest extends TestCase
{
    public function testConnects()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('connect');

        (new PlayCommand($conn));
    }

    public function testStartingUp()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('startup');

        (new PlayCommand($conn));
    }

    public function testStartupOption()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->never())
            ->method('startup');

        (new PlayCommand($conn, ['startup' => false]));
    }

    public function testWriteCommand()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Q::ident LENGTH "SELECT 1"::string NUL'));

        $play_cmd = new PlayCommand($conn);
        $input = <<<'IN'
write Q::ident LENGTH "SELECT 1"::string NUL
IN;
        $play_cmd->run($input);
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

        $play_cmd = new PlayCommand($conn);
        $input = <<<'IN'
write Q::ident LENGTH "SELECT 1"::string NUL
write Q::ident LENGTH "SELECT 2"::string NUL
IN;
        $play_cmd->run($input);
    }
    
    public function testRunFailsBadCommand()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid command");

        $conn = $this->createMock(ConnectionInterface::class);

        $play_cmd = new PlayCommand($conn);
        $play_cmd->run('... fail');
    }

    public function testRunNoInput()
    {
        $conn = $this->createMock(ConnectionInterface::class);

        $play_cmd = new PlayCommand($conn);
        $this->assertTrue($play_cmd->run(""));
        $this->assertTrue($play_cmd->run("\n\n"));
    }

    public function testReadCommandValidates()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("*read* expects length to be an integer");

        $conn = $this->createMock(ConnectionInterface::class);

        $play_cmd = new PlayCommand($conn);
        $input = <<<'IN'
read ...
IN;
        $play_cmd->run($input);
    }

    public function testReadCommand()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())
            ->method('read')
            ->with($this->equalTo(25));

        $play_cmd = new PlayCommand($conn);
        $input = <<<'IN'
read 25
IN;
        $play_cmd->run($input);
    }
}
