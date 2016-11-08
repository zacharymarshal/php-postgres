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
        $this->msg .= $ident;
    }

    /**
     * Write a null character to the message
     */
    public function writeNUL()
    {
        $this->msg .= "\0";
    }

    /**
     * @param int $int
     */
    public function writeInt32($int)
    {
        $this->msg .=  pack('N', $int);
    }

    /**
     * @param int $int
     */
    public function writeInt16($int)
    {
        $this->msg .= pack('n', $int);
    }

    /**
     * @param $str
     */
    public function writeString($str)
    {
        $this->msg .= $str;
    }
}
