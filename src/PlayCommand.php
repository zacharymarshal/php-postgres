<?php

namespace Postgres;

use InvalidArgumentException;

/**
 * Responsible for parsing the individual commands
 * and calling the appropriate connection methods
 *
 * @package Postgres
 */
class PlayCommand
{
    /**
     * @var ConnectionInterface
     */
    private $conn;

    /**
     * @param ConnectionInterface $conn
     * @param array $options
     */
    public function __construct(ConnectionInterface $conn, array $options = [])
    {
        $this->conn = $conn;
        $options = array_merge([
            'startup' => true
        ], $options);

        $this->conn->connect();

        if ($options['startup'] === true) {
            $this->conn->startup();
        }
    }

    /**
     * @param string $input
     * @return bool
     */
    public function run(string $input): bool
    {
        foreach (explode("\n", $input, 1000) as $cmd) {
            $this->runCmd($this->conn, $cmd);
        }

        return true;
    }

    /**
     * @param ConnectionInterface $conn
     * @param string $cmd
     */
    private function runCmd(ConnectionInterface $conn, string $cmd)
    {
        $cmd = trim($cmd);
        if ($cmd === '') {
            return;
        }

        if (substr($cmd, 0, 6) === 'write ') {
            $conn->write(substr($cmd, 6));
            return;
        } elseif (substr($cmd, 0, 5) === 'read ') {
            $length = substr($cmd, 5);
            if ($length != (string) (int) $length) {
                throw new InvalidArgumentException(sprintf(
                    "*read* expects length to be an integer, not \"%s\"",
                    $length
                ));
            }
            $conn->read((int) $length);
            return;
        } elseif (substr($cmd, 0, 11) === 'readMessage') {
            $conn->readMessage();
            return;
        } elseif (substr($cmd, 0, 8) === 'readByte') {
            $conn->readByte();
            return;
        } elseif (substr($cmd, 0, 9) === 'readInt32') {
            $conn->readInt32();
            return;
        }

        throw new InvalidArgumentException("Invalid command {$cmd}");
    }
}
