<?php

namespace Postgres;

class Connection
{
    private $url;
    private $port;
    private $connect_timeout;

    private $conn;

    /**
     * @param $url string postgres://user:pass@localhost:5432/dbname?param=val
     */
    public function __construct(string $url, int $connect_timeout = null)
    {
        $this->url = $url;
        $this->connect_timeout = $connect_timeout;
    }

    public function connect()
    {
        if ($this->conn) {
            return;
        }

        $host = parse_url($this->url, PHP_URL_HOST);
        $port = parse_url($this->url, PHP_URL_PORT) ?: 5432;
        $ip = gethostbyname($host);
        $socket = "tcp://{$ip}:{$port}";
        $this->conn = stream_socket_client(
            $socket,
            $error_code,
            $error_message,
            $this->connect_timeout ?: ini_get('default_socket_timeout')
        );

        if (!$this->conn) {
            throw new PostgresException(
                sprintf("Failed to connect to %s (%s)", $socket,
                    $error_message)
            );
        }

        // Write startup message
        $user = parse_url($this->url, PHP_URL_USER);
        $database = substr(parse_url($this->url, PHP_URL_PATH), 1);
        parse_str(parse_url($this->url, PHP_URL_QUERY), $params);

        $pk_format = 'nnZ*Z*Z*Z*';
        $pk_args = [3, 0, 'user', $user, 'database', $database];
        foreach ($params as $param => $val) {
            $pk_format .= 'Z*Z*';
            $pk_args[] = $param;
            $pk_args[] = $val;
        }
        $pk_format .= 'x';

        $msg = call_user_func_array('pack', array_merge([$pk_format], $pk_args));
        $msg = pack('N', strlen($msg) + 4) . $msg;
        $this->write($msg);

        while (true) {
            list($ident, , $buf) = $this->readMessage();

            if ($ident === 'R') {
                $this->authenticate($buf);
            }

            if ($ident === 'E') {
                throw new PostgresException("Error connecting to {$user}@{$database}");
            }

            if ($ident === 'Z') {
                return;
            }
        }
    }

    public function query(string $sql): array
    {
        $msg = pack('Z*', $sql);
        $msg = pack('a1N', 'Q', strlen($msg) + 4) . $msg;
        $this->write($msg);

        $rows = [];
        while (true) {
            list($ident, , $buf) = $this->readMessage();

            if ($ident === 'T') {
                $fields = [];
                $num_fields = $buf->readInt16();
                for ($i = 0; $i < $num_fields; $i++) {
                    $fields[$i] = $buf->readString();
                    $buf->readInt32();
                    $buf->readInt16();
                    $buf->readInt32();
                    $buf->readInt16();
                    $buf->readInt32();
                    $buf->readInt16();
                }
            }

            if ($ident === 'D') {
                $row = [];
                $num_cols = $buf->readInt16();
                for ($i = 0; $i < $num_cols; $i++) {
                    $col_val_len = $buf->readInt32();

                    if ($col_val_len === 0xFFFFFFFF) {
                        $value = null;
                    } elseif ($col_val_len === 0) {
                        $value = '';
                    } else {
                        $value = $buf->read($col_val_len);
                    }
                    $row[$fields[$i]] = $value;
                }
                $rows[] = $row;
            }

            if ($ident === 'E') {
                throw new PostgresException("Error running query");
            }

            if ($ident === 'Z') {
                break;
            }
        }

        return $rows;
    }

    private function write(string $string)
    {
        if (fwrite($this->conn, $string) === false) {
            throw new PostgresException("Failed to write to postgres");
        }
    }

    /**
     * @return array [ident, length, ReadBuffer]
     */
    private function readMessage(): array
    {
        $ident = $this->readByte();
        $length = $this->readInt32() - 4; // includes itself
        $buffer = new ReadBuffer($this->read($length));

        return [$ident, $length, $buffer];
    }

    private function readByte(): string
    {
        return $this->read(1);
    }

    /**
     * @return int
     */
    private function readInt32(): int
    {
        $unpack = unpack('Nint32', $this->read(4));

        return $unpack['int32'];
    }

    /**
     * @param int $len
     * @return string
     */
    private function read(int $len): string
    {
        $buffer = fread($this->conn, $len);
        if ($buffer === false) {
            throw new PostgresException("Failed to read from postgres");
        }

        return $buffer;
    }

    private function authenticate(ReadBuffer $buf): bool
    {
        $auth_code = $buf->readInt32();

        // Authentication successful
        if ($auth_code === 0) {
            return true;
        }

        // Plain text authentication
        if ($auth_code === 3) {
            $password = parse_url($this->url, PHP_URL_PASS);
            $msg = pack('Z*', $password);
            $msg = pack('a1N', 'p', strlen($msg) + 4) . $msg;
            $this->write($msg);

            list($ident, , $buf) = $this->readMessage();
            if ($ident !== 'R') {
                throw new PostgresException(sprintf("Failed to authenticate."
                    . " Unexpected message '%s'.", $ident));
            }
            $auth_code = $buf->readInt32();
            if ($auth_code !== 0) {
                throw new PostgresException(sprintf("Failed to authenticate."
                    . " Unexpected auth code '%s'", $auth_code));
            }

            return true;
        }
    }
}
