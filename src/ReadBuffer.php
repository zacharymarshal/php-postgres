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

    private $offset = 0;

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
        return $this->read();
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
        $str = '';
        while ($char = $this->read(1)) {
            if ($char === "\0") {
                break;
            }
            $str .= $char;
        }

        return $str;
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
    public function read(int $length = null): string
    {
        $bytes = substr($this->buffer, $this->offset, $length);
        $this->offset += $length;

        return $bytes;
    }
}
