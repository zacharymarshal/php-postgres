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
        $conn_opts = array_replace($defaults, array_intersect_key($this->options, $defaults));
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

    public function startup()
    {
        $this->writeStartup();

        while (true) {
            list($ident, , $buffer) = $this->readMessage();
            if ($ident === 'R') {
                $this->authenticate($buffer);
            }
            if ($ident === 'E') {
                throw new PostgresException("TODO: Error! {$buffer}");
            }
            if ($ident === 'Z') {
                // TODO: Set some sort of state letting us know that
                // we are ready for a query
                return;
            }
        }
    }

    /**
     * @param string $msg
     */
    public function write(string $msg)
    {
        $frontend_msg = (new FrontendMessageParser($msg))->getMessage();
        fwrite($this->conn, $frontend_msg);
    }

    /**
     * @return array [ident, length, ReadBuffer]
     */
    public function readMessage(): array
    {
        $ident = $this->readByte();
        $length = $this->readInt32() - 4; // includes itself
        $buffer = new ReadBuffer($this->read($length));
        return [$ident, $length, $buffer];
    }

    /**
     * @return string
     */
    public function readByte(): string
    {
        return $this->read(1);
    }

    /**
     * @return int
     */
    public function readInt32(): int
    {
        $unpack = unpack('Nint32', $this->read(4));
        return $unpack['int32'];
    }

    /**
     * @param int $len
     * @return string
     */
    public function read(int $len): string
    {
        $buffer = fread($this->conn, $len);
        return $buffer;
    }

    private function writeStartup()
    {
        $defaults = [
            'user'             => 'postgres',
            'database'         => 'postgres',
            'application_name' => 'php-postgres',
            'client_encoding'  => 'UTF8',
            'DateStyle'        => 'ISO, MDY',
        ];
        $startup_params = array_replace($defaults, array_intersect_key($this->options, $defaults));

        $msg = "LENGTH 3::int16 0::int16";
        foreach ($startup_params as $option => $value) {
            $msg .= " \"{$option}\" NUL \"{$value}\" NUL";
        }
        $msg .= " NUL";

        $this->write($msg);
    }

    /**
     * @param ReadBuffer $buffer
     * @return bool
     */
    private function authenticate(ReadBuffer $buffer): bool
    {
        $auth_code = $buffer->readInt32();
        if ($auth_code === 0) {
            return true;
        }

        if ($auth_code === 3) {
            $password = $this->options['pass'] ?? '';
            $this->write("p::ident LENGTH \"{$password}\" NUL");

            list($ident, , $buffer) = $this->readMessage();
            if ($ident !== 'R') {
                throw new PostgresException("TODO: Unexpected message {$ident}");
            }
            $auth_code = $buffer->readInt32();
            if ($auth_code !== 0) {
                throw new PostgresException("TODO: Unexpected auth code {$auth_code}");
            }

            return true;
        }
    }
}
