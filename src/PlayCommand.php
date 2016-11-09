<?php

namespace Postgres;

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

        return true;
    }
}
