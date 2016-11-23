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
    private $ident = '';

    /**
     * @var bool
     */
    private $include_length = false;

    /**
     * @var FrontendMessageLexer
     */
    private $lexer;

    /**
     * @param string $str_msg
     */
    public function __construct(string $str_msg)
    {
        $this->lexer = new FrontendMessageLexer($str_msg);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $buff = new WriteBuffer();
        while ($token = $this->lexer->nextToken()) {
            switch ($token['type']) {
                case 'constant':
                    $this->writeConstant($token['value'], $buff);
                    break;
                case 'ident':
                    $this->ident = $token['value'];
                    break;
                case 'int16':
                    $buff->writeInt16($token['value']);
                    break;
                case 'int32':
                    $buff->writeInt32($token['value']);
                    break;
                case 'string':
                    $buff->writeString($token['value']);
                    break;
                default:
                    break;
            }
        }

        $buff_start = new WriteBuffer();
        $buff_start->writeIdent($this->ident);
        if ($this->include_length === true) {
            // message length including the length itself–4 bytes
            $buff_start->writeInt32(strlen($buff) + 4);
        }

        return $buff_start . $buff;
    }

    /**
     * @param string $value
     * @param WriteBuffer $buff
     */
    private function writeConstant(string $value, WriteBuffer $buff)
    {
        switch ($value) {
            case 'NUL':
                $buff->writeNUL();
                break;
            case 'LENGTH':
                $this->include_length = true;
                break;
        }
    }
}
