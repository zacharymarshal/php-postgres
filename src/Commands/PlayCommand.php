<?php

namespace Postgres\Commands;

use Postgres\BackendMessage;
use Postgres\FrontendMessage;
use Postgres\FrontendMessageLexer;
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
        $helper = $this->getHelper('question');
        $addr = gethostbyname('localhost');
        $socket = "tcp://$addr:5432";
        $output->writeln("<comment>Connecting to {$socket}</comment>");
        $client = stream_socket_client($socket, $errno, $errorMessage, 10);
        $output->writeln("<info>Connected</info>");
        do {
            $user_input = readline('> ');
            if (!empty($user_input)) {
                readline_add_history($user_input);
            }

            $params = explode(' ', $user_input);
            $command = array_shift($params);

            if ($command === 'send') {
                $str_msg = preg_replace("/^send\s+/", "", $user_input);
                $lexer = new FrontendMessageLexer($str_msg);
                $include_length = false;
                $msg_ident = false;
                $message = new FrontendMessage();
                while ($token = $lexer->nextToken()) {
                    if ($token['type'] === 'int16') {
                        $message->writeInt16($token['value']);
                    } elseif ($token['type'] === 'int32') {
                        $message->writeInt32($token['value']);
                    } elseif ($token['type'] === 'string') {
                        $message->writeString($token['value']);
                    } elseif ($token['type'] === 'constant'
                        && $token['value'] === 'NUL'
                    ) {
                        $message->writeNUL();
                    } elseif ($token['type'] === 'constant'
                        && $token['value'] === 'LENGTH'
                    ) {
                        $include_length = true;
                    } elseif ($token['type'] === 'ident') {
                        $msg_ident = $token['value'];
                    }
                }

                $full_message = '';
                if ($msg_ident) {
                    $full_message .= "{$msg_ident}";
                }
                if ($include_length === true) {
                    $length = strlen($message) + 4; // including itself, 4 bytes
                    $full_message .= pack('N', $length);
                }
                $full_message .= "{$message}";

                $output->writeln("<comment>Sending message >> {$full_message}</comment>");
                fwrite($client, $full_message);
                $output->writeln("<info>Sent</info>");
            } elseif ($command === 'send_startup') {
                $options = $this->getOptions($user_input);
                $startup = new FrontendMessage();
                $startup->writeInt16(3);
                $startup->writeInt16(0);
                $startup->writeString('user');
                $startup->writeNUL();
                $startup->writeString($options['user']);
                $startup->writeNUL();
                $startup->writeString('database');
                $startup->writeNUL();
                $startup->writeString($options['database']);
                $startup->writeNUL();
                $length = strlen($startup) + 4; // including itself, 4 bytes

                $output->writeln("<comment>Sending startup message</comment>");
                fwrite($client, pack('N', $length) . $startup);
                $output->writeln("<info>Sent</info>");
            } elseif ($command === 'get') {
                $client_output = fread($client, array_shift($params));
                $output->writeln($client_output);
            } elseif ($command === 'get_message') {
                list($message_code, $message_length, $message) = $this->getMessage($client);
                $output->writeln("<info>{$message_code} (length $message_length)</info>");
                $output->writeln("<info>{$message}</info>");
            } elseif ($command === 'get_messages') {
                do {
                    list($message_code, $message_length, $message) = $this->getMessage($client);
                    $output->writeln("{$message_code} (length $message_length)");
                    $output->writeln("{$message}");
                    $output->writeln("<comment>---------------------------------</comment>");
                } while ($message_code !== 'Z');
            }
        } while ($user_input !== 'exit');
    }

    /**
     * @param $client
     * @return array
     */
    private function getMessage($client)
    {
        $msg_ident = fread($client, 1);
        $msg_length = current(unpack('N', fread($client, 4)));
        $msg_body = fread($client, $msg_length - 4);
        $msg = new BackendMessage($msg_ident, $msg_body);
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

    /**
     * @param $user_input
     * @return array
     */
    private function getOptions($user_input)
    {
        $options = [];
        foreach (preg_split('/\s+/', $user_input) as $param) {
            if (preg_match('/--(.+)=(.+)/', $param, $matches)) {
                $options[$matches[1]] = $matches[2];
            }
        }

        return $options;
    }
}
