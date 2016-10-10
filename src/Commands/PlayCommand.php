<?php

namespace Postgres\Commands;

use Postgres\Connection;
use Postgres\FrontendMessage;
use Postgres\FrontendMessageLexer;
use Postgres\FrontendMessageParser;
use Postgres\ReadBuffer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PlayCommand extends Command
{
    protected function configure()
    {
        $this->setName('play')->setDescription('Play');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = null;
        do {
            $user_input = readline('> ');
            if (!empty($user_input)) {
                readline_add_history($user_input);
            }

            $params = explode(' ', $user_input);
            $command = array_shift($params);
            if ($command === 'connect') {
                $url = current($params);
                $conn = new Connection($url);
                $output->writeln("<comment>Connecting to {$url}</comment>");
                $conn->connect();
                $output->writeln("<info>Connected</info>");
            } elseif ($command === 'send') {
                $str_msg = preg_replace("/^send\s+/", "", $user_input);
                $output->writeln("<comment>Sending message >> {$str_msg}</comment>");
                $conn->write($str_msg);
                $output->writeln("<info>Sent</info>");
            } elseif ($command === 'send_startup') {
                $output->writeln("<comment>Sending startup message</comment>");
                $conn->startup();
                $output->writeln("<info>Sent</info>");
            } elseif ($command === 'get') {
                $client_output = $conn->read(array_shift($params));
                $output->writeln($client_output);
            } elseif ($command === 'get_message') {
                list($message_code, $message_length, $message) = $this->getMessage($conn);
                $output->writeln("<info>{$message_code} (length $message_length)</info>");
                $output->writeln("<info>{$message}</info>");
            }
        } while ($user_input !== 'exit');
    }

    /**
     * @param Connection $conn
     * @return array
     */
    private function getMessage(Connection $conn)
    {
        /** @var ReadBuffer $msg */
        list($msg_ident, $msg_length, $msg) = $conn->readMessage();
        if ($msg_ident === 'R') {
            return [$msg_ident, $msg_length, $msg->readInt32()];
        } elseif ($msg_ident === 'S') {
            return [
                $msg_ident,
                $msg_length,
                json_encode([
                    $msg->readString(),
                    $msg->readString()
                ])
            ];
        } elseif ($msg_ident === 'Z') {
            return [$msg_ident, $msg_length, $msg->readByte()];
        } elseif ($msg_ident === 'K') {
            return [
                $msg_ident,
                $msg_length,
                json_encode([
                    'process_id' => $msg->readInt32(),
                    'secret_key' => $msg->readInt32(),
                ])
            ];
        } else {
            return [$msg_ident, $msg_length, "{$msg}"];
        }
    }
}
