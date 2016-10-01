<?php

namespace Postgres;

class FrontendMessageLexer
{
    /**
     * @var string
     */
    private $msg;

    /**
     * @param string $msg
     */
    public function __construct(string $msg)
    {
        $this->msg = $msg;
    }

    /**
     * @return array
     */
    public function nextToken()
    {
        $length = strlen($this->msg);
        if ($length === 0) {
            return [];
        }

        list($token, $token_str) = $this->extractToken($this->msg);
        $this->msg = substr($this->msg, strlen($token_str));

        return $token;
    }

    /**
     * @param string $msg
     * @return array
     */
    private function extractToken(string $msg): array
    {
        if (preg_match("/^\s+/", $msg, $matches)) {
            return [
                ['type' => 'whitespace', 'value'  => $matches[0]],
                $matches[0]
            ];
        }

        if (preg_match("/^(\d+)::int16/", $msg, $matches)) {
            return [
                ['type' => 'int16', 'value'  => $matches[1]],
                $matches[0]
            ];
        }

        return [
            ['type' => 'unknown', 'value' => $msg],
            $msg
        ];
    }
}
