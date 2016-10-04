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
        $int32 = unpack('Nint', substr($this->buffer, 0, 4));
        $this->buffer = substr($this->buffer, 4);

        return $int32['int'];
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
        $string = substr($this->buffer, 0, $nul_pos);
        $this->buffer = substr($this->buffer, $nul_pos + 1);

        return $string;
    }

    /**
     * @return string
     */
    public function readByte(): string
    {
        $byte = substr($this->buffer, 0, 1);
        $this->buffer = substr($this->buffer, 1);

        return $byte;
    }
}
