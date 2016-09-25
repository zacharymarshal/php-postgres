<?php

namespace Postgres;

/**
 * @param $url
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
    $msg_pieces = [];
    foreach (explode(' ', $msg) as $msg_piece) {
        $msg_piece = str_replace('\0', "\0", $msg_piece);
        if (preg_match('/([^\s]+)::int16$/', $msg_piece, $matches)) {
            $msg_piece = pack('n', $matches[1]);
        } elseif (preg_match('/([^\s]+)::int32$/', $msg_piece, $matches)) {
            $msg_piece = pack('N', $matches[1]);
        }
        $msg_pieces[] = $msg_piece;
    }

    $msg = implode('', $msg_pieces);
    $msg_length = pack('N', strlen($msg) + 4); // include itself 4 bytes
    $msg = $msg_code . $msg_length . $msg;
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
