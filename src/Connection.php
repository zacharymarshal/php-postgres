<?php

namespace Postgres;

/**
 * @package Postgres
 */
class Connection
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var resource
     */
    private $conn;

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        $options = parse_url($url);
        parse_str($options['query'] ?? '', $more_options);
        $this->options = array_merge($options, $more_options);
    }

    /**
     * Make a connection to the backend postgres server
     */
    public function connect()
    {
        $defaults = [
            'scheme'          => 'tcp',
            'host'            => 'localhost',
            'port'            => 5432,
            'connect_timeout' => 1,
        ];
        $conn_opts = array_merge($defaults, $this->options);
        $host = gethostbyname($conn_opts['host']);
        $this->conn = stream_socket_client(
            "{$conn_opts['scheme']}://{$host}:{$conn_opts['port']}",
            $error_code,
            $error_message,
            $conn_opts['connect_timeout']
        );

        if (!$this->conn) {
            throw new PostgresException(
                sprintf("%s (%s)", $error_message, $error_code),
                $error_code
            );
        }
    }
}
