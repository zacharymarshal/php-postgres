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
     * @param ConnectionInterface $conn
     * @param string $input
     * @param array $options
     * @return bool
     */
    public function run(ConnectionInterface $conn, string $input, array $options = []): bool
    {
        $options = array_merge([
            'startup' => true
        ], $options);

        $conn->connect();

        if ($options['startup'] === true) {
            $conn->startup();
        }

        foreach (explode("\n", $input, 1000) as $cmd) {
            $this->runCmd($conn, $cmd);
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
        }

        throw new InvalidArgumentException("Invalid command {$cmd}");
    }
}
