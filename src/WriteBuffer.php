<?php

namespace Postgres;

/**
 * Represents a string to be sent to the postgres backend.
 *
 * @package Postgres
 */
class WriteBuffer
{
    /**
     * @var string
     */
    private $msg = "";

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->msg;
    }

    /**
     * @param string $ident
     */
    public function writeIdent($ident)
    {
        $this->write($ident);
    }

    /**
     * Write a null character to the message
     */
    public function writeNUL()
    {
        $this->write(pack('x'));
    }

    /**
     * @param int $int
     */
    public function writeInt32($int)
    {
        $this->write(pack('N', $int));
    }

    /**
     * @param int $int
     */
    public function writeInt16($int)
    {
        $this->write(pack('n', $int));
    }

    /**
     * @param $str
     */
    public function writeString($str)
    {
        $this->write($str);
        $this->writeNUL();
    }

    /**
     * @param string $bytes
     */
    public function write(string $bytes)
    {
        $this->msg .= $bytes;
    }
}
