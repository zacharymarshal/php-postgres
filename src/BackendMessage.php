<?php

namespace Postgres;

use Exception;

/**
 * Represents a message sent from the postgres backend.
 *
 * @package Postgres
 */
class BackendMessage
{
    /**
     * @var string
     */
    private $ident;

    /**
     * @var string
     */
    private $msg_body;

    /**
     * @param string $ident
     * @param string $msg_body
     */
    public function __construct(string $ident, string $msg_body)
    {
        $this->ident = $ident;
        $this->msg_body = $msg_body;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->msg_body;
    }

    /**
     * @return string
     */
    public function getIdent(): string
    {
        return $this->ident;
    }

    /**
     * @return int
     */
    public function readInt32(): int
    {
        $int32 = unpack('Nint', substr($this->msg_body, 0, 4));

        $this->msg_body = substr($this->msg_body, 4);

        return $int32['int'];
    }

    /**
     * @return string
     * @throws Exception
     */
    public function readString(): string
    {
        $nul_pos = strpos($this->msg_body, "\0");
        if ($nul_pos === false) {
            throw new Exception("Could not read string.  Missing string terminator NUL.");
        }
        $string = substr($this->msg_body, 0, $nul_pos);
        $this->msg_body = substr($this->msg_body, $nul_pos + 1);

        return $string;
    }

    /**
     * @return string
     */
    public function readByte(): string
    {
        $byte = substr($this->msg_body, 0, 1);
        $this->msg_body = substr($this->msg_body, 1);

        return $byte;
    }
}
