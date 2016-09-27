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

    sendStartupMessage($conn, $db_opts['user'], $db_opts['pass'], ltrim($db_opts['path'], '/'));

    return $conn;
}

/**
 * @param $conn
 * @param string $user
 * @param $password
 * @param string $database
 * @throws \Exception
 */
function sendStartupMessage($conn, $user, $password, $database)
{
    send($conn, sprintf(
        '3::int16 0::int16 "user\0%s\0database\0%s\0"::string',
        $user,
        $database
    ));

    while (true) {
        list($msg, $msg_code) = get($conn);
        if ($msg_code === 'R') {
            authenticate($conn, $msg, $password);
        }
        if ($msg_code === 'E') {
            throw new \Exception("TODO: Error! " . $msg);
        }
        if ($msg_code === 'Z') {
            // TODO: Set some sort of state letting us know that
            // we are ready for a query
            return;
        }
    }
}

/**
 * @param $conn
 * @param $binary_auth_code
 * @param $password
 * @throws \Exception
 */
function authenticate($conn, $binary_auth_code, $password)
{
    $auth_code = int32($binary_auth_code);
    if ($auth_code === 0) {
        return;
    }

    if ($auth_code === 3) {
        send($conn, sprintf('"%s"::string', $password), 'p');
        list($binary_auth_code, $msg_code) = get($conn);
        if ($msg_code != 'R') {
            throw new \Exception("TODO: Unexpected response code " . $msg_code);
        }
        $auth_code = int32($binary_auth_code);
        if ($auth_code !== 0) {
            throw new \Exception("TODO: Unexpected auth code " . $auth_code);
        }
        return;
    }
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
 * @return array
 * @throws \Exception
 */
function get($conn)
{
    $msg_code_result = socket_recv($conn, $bin_msg_code, 5, MSG_WAITALL);
    if ($msg_code_result === false) {
        throw new \Exception("TODO: Error getting message code");
    }
    $msg_code = unpack('a1code/N1length', $bin_msg_code);
    $get_msg_result = socket_recv($conn, $msg, $msg_code['length'] - 4, 0);
    if ($get_msg_result === false) {
        throw new \Exception("TODO: Error getting message data");
    }

    return [$msg, $msg_code['code']];
}

/**
 * @param $msg
 * @return string
 */
function createMessage($msg)
{
    $msg_tokens = tokenizeMessage($msg);
    $insert_length = false;
    $msg_body = '';
    foreach ($msg_tokens as $msg_token) {
        switch ($msg_token['type']) {
            case 'code':
                $msg_code = $msg_token['code'];
                break;
            case 'const':
                if ($msg_token['value'] === '\0') {
                    $msg_body .= "\0";
                } elseif ($msg_token['value'] === 'LENGTH') {
                    $insert_length = true;
                }
                break;
            case 'int16':
                $msg_body .= pack('n', $msg_token['number']);
                break;
            case 'int32':
                $msg_body .= pack('N', $msg_token['number']);
                break;
            case 'string':
                // Replace zero byte characters
                $string = str_replace('\0', "\0", $msg_token['string']);
                $msg_body .= $string;
                break;
        }
    }

    $new_msg = '';
    if (isset($msg_code)) {
        $new_msg .= $msg_code;
    }
    if ($insert_length === true) {
        $new_msg .= pack('N', strlen($msg_body) + 4); // include itself 4 bytes
    }
    $new_msg .= $msg_body;

    return $new_msg;
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

    if (preg_match("/^([A-Za-z])::code/", $msg, $matches)) {
        return [
            'type'  => 'code',
            'value' => $matches[0],
            'code'  => $matches[1],
        ];
    }

    if (strpos($msg, '\0') === 0) {
        return [
            'type'  => 'const',
            'value' => '\0',
        ];
    }

    if (preg_match('/^LENGTH/', $msg, $matches)) {
        return [
            'type'  => 'const',
            'value' => $matches[0],
        ];
    }

    return [
        'type'  => 'unknown',
        'value' => $msg,
    ];
}

/**
 * @param $str
 * @return string
 * @throws \Exception
 */
function int32(&$str)
{
    $binary_int = substr($str, 0, 4);
    if (!$binary_int) {
        throw new \Exception("TODO: Not enough bytes");
    }
    $str = substr($str, 4);

    $int32 = current(unpack('N', $binary_int));

    return $int32;
}
