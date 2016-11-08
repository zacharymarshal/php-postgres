<?php

namespace Postgres;

/**
 * @package Postgres
 */
class FrontendMessageParser
{
    /**
     * @var string
     */
    private $str_msg;

    /**
     * @param string $str_msg
     */
    public function __construct(string $str_msg)
    {
        $this->str_msg = $str_msg;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $lexer = new FrontendMessageLexer($this->str_msg);
        $include_length = false;
        $msg_ident = false;
        $buff = new WriteBuffer();
        while ($token = $lexer->nextToken()) {
            if ($token['type'] === 'int16') {
                $buff->writeInt16($token['value']);
            } elseif ($token['type'] === 'int32') {
                $buff->writeInt32($token['value']);
            } elseif ($token['type'] === 'string') {
                $buff->writeString($token['value']);
            } elseif ($token['type'] === 'constant'
                && $token['value'] === 'NUL'
            ) {
                $buff->writeNUL();
            } elseif ($token['type'] === 'constant'
                && $token['value'] === 'LENGTH'
            ) {
                $include_length = true;
            } elseif ($token['type'] === 'ident') {
                $msg_ident = $token['value'];
            }
        }

        $msg = new WriteBuffer();
        if ($msg_ident) {
            $msg->writeString($msg_ident);
        }
        if ($include_length === true) {
            // message length including the length itself–4 bytes
            $msg->writeInt32(strlen($buff) + 4);
        }
        $msg->writeString($buff->__toString());

        return $msg;
    }
}
