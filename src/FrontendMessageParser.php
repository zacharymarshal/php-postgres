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
        $front_msg = new FrontendMessage();
        while ($token = $lexer->nextToken()) {
            if ($token['type'] === 'int16') {
                $front_msg->writeInt16($token['value']);
            } elseif ($token['type'] === 'int32') {
                $front_msg->writeInt32($token['value']);
            } elseif ($token['type'] === 'string') {
                $front_msg->writeString($token['value']);
            } elseif ($token['type'] === 'constant'
                && $token['value'] === 'NUL'
            ) {
                $front_msg->writeNUL();
            } elseif ($token['type'] === 'constant'
                && $token['value'] === 'LENGTH'
            ) {
                $include_length = true;
            } elseif ($token['type'] === 'ident') {
                $msg_ident = $token['value'];
            }
        }

        $msg = '';
        if ($msg_ident) {
            $msg .= "{$msg_ident}";
        }
        if ($include_length === true) {
            $length = strlen($front_msg) + 4; // including itself, 4 bytes
            $msg .= pack('N', $length);
        }
        $msg .= "{$front_msg}";

        return $msg;
    }
}
