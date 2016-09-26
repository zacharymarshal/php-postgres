<?php

namespace Postgres;

/**
 * @param $url
 * @param array $options
 * @return resource
 * @throws \Exception
 */
function connect($url, array $options = [])
{
    $timeout = 1; // in seconds
    extract($options, EXTR_IF_EXISTS);
    $conn = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!$conn) {
        throw new \Exception("TODO: Could not create socket");
    }
    // Set timeout
    socket_set_option($conn, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);

    $db_opts = parse_url($url);
    $host = gethostbyname($db_opts['host']);
    $conn_result = socket_connect($conn, $host, $db_opts['port']);
    if (!$conn_result) {
        throw new \Exception("TODO: Could not connect");
    }

    return $conn;
}

/**
 * @param $conn
 */
function disconnect($conn)
{
    socket_close($conn);
}

/**
 * @param $conn
 * @param $msg
 * @param string $msg_code
 * @throws \Exception
 */
function send($conn, $msg, $msg_code = '')
{
    $msg = createMessage($msg, $msg_code);
    $send_result = socket_send($conn, $msg, strlen($msg), 0);
    if (!$send_result) {
        throw new \Exception("TODO: Send failed");
    }
}

/**
 * @param $conn
 * @throws \Exception
 */
function get($conn, callable $callback)
{
    $read = $write = $except = [];
    $read[] = $conn;
    $can_read = socket_select($read, $write, $except, 1);
    if ($can_read === 0) {
        return;
    }

    while (($r = socket_recv($conn, $buf, 5, 0))) {
        $unpacked_buf = unpack('a1msg_code/N1msg_length', $buf);
        $get_msg_result = socket_recv($conn, $msg, $unpacked_buf['msg_length'] - 4, 0);
        if ($get_msg_result === false) {
            throw new \Exception("TODO: Error getting message data");
        }

        call_user_func($callback, $unpacked_buf['msg_code'], $msg);
    }

    if ($r === false) {
        var_dump(socket_strerror(socket_last_error()));
        exit;
    }
}

/**
 * @param $msg
 * @param string $msg_code
 * @return string
 */
function createMessage($msg, $msg_code = '')
{
    $msg_tokens = tokenizeMessage($msg);
    $new_msg = '';
    foreach ($msg_tokens as $msg_token) {
        switch ($msg_token['type']) {
            case 'int16':
                $new_msg .= pack('n', $msg_token['number']);
                break;
            case 'int32':
                $new_msg .= pack('N', $msg_token['number']);
                break;
            case 'string':
                $new_msg .= str_replace('\0', "\0", $msg_token['string']);
                break;
        }
    }
    $new_msg .= "\0"; // End with NULL character

    $msg_length = pack('N', strlen($new_msg) + 4); // include itself 4 bytes

    return $msg_code . $msg_length . $new_msg;
}

/**
 * @param string $msg
 * @return array
 */
function tokenizeMessage($msg)
{
    $tokens = [];
    $length = strlen($msg);
    while ($length) {
        $token = $tokens[] = getMessageToken($msg);

        $token_length = strlen($token['value']);
        $msg = substr($msg, $token_length);
        $length -= $token_length;
    }

    return $tokens;
}

/**
 * @param string $msg
 * @return array
 */
function getMessageToken($msg)
{
    if (preg_match("/^(\d+)::int16/", $msg, $matches)) {
        return [
            'type'   => 'int16',
            'value'  => $matches[0],
            'number' => $matches[1],
        ];
    }

    if (preg_match("/^(\d+)::int32/", $msg, $matches)) {
        return [
            'type'   => 'int32',
            'value'  => $matches[0],
            'number' => $matches[1],
        ];
    }

    if (preg_match("/^\"(.+)\"::string/", $msg, $matches)) {
        return [
            'type'   => 'string',
            'value'  => $matches[0],
            'string' => $matches[1],
        ];
    }

    if (preg_match("/^\s+/", $msg, $matches)) {
        return [
            'type'   => 'whitespace',
            'value'  => $matches[0],
        ];
    }

    return [
        'type'  => 'unknown',
        'value' => $msg,
    ];
}
