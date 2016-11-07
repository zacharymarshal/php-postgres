<?php

namespace Postgres;

use Exception;

/**
 * Represents a message sent from the postgres backend.
 *
 * @package Postgres
 */
class ReadBuffer
{
    /**
     * @var string
     */
    private $buffer;

    /**
     * @param string $buffer
     */
    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->buffer;
    }

    /**
     * @return int
     */
    public function readInt32(): int
    {
        $int32 = unpack('Nint', $this->read(4));

        return $int32['int'];
    }

    /**
     * @return int
     */
    public function readInt16(): int
    {
        $int16 = unpack('nint', $this->read(2));

        return $int16['int'];
    }

    /**
     * @return string
     * @throws Exception
     */
    public function readString(): string
    {
        $nul_pos = strpos($this->buffer, "\0");
        if ($nul_pos === false) {
            throw new Exception("Could not read string.  Missing string terminator NUL.");
        }
        $string = rtrim($this->read($nul_pos + 1), "\0");

        return $string;
    }

    /**
     * @return string
     */
    public function readByte(): string
    {
        $byte = $this->read(1);

        return $byte;
    }

    /**
     * @param int $length
     * @return string
     */
    public function read(int $length): string
    {
        $bytes = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, $length);

        return $bytes;
    }
}
