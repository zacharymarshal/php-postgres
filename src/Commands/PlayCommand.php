<?php

namespace Postgres\Commands;

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
                foreach ($params as &$param) {
                    if (preg_match('/([^\s]+)::int16$/', $param, $matches)) {
                        $param = pack('n', $matches[1]);
                    } elseif (preg_match('/([^\s]+)::int32$/', $param, $matches)) {
                        $param = pack('N', $matches[1]);
                    } elseif (strstr($param, '\0')) {
                        $param = str_replace('\0', "\0", $param);
                    }
                }

                $message = implode('', $params);

                $output->writeln("<comment>Sending message >> {$message}</comment>");
                fwrite($client, $message);
                $output->writeln("<info>Sent</info>");
            } elseif ($command === 'get') {
                $client_output = fread($client, array_shift($params));
                $output->writeln($client_output);
            } elseif ($command === 'get_message') {
                list($message_code, $message_length, $message) = $this->get_message($client);
                $output->writeln("<info>{$message_code} (length $message_length)</info>");
                $output->writeln("<info>{$message}</info>");
            } elseif ($command === 'get_messages') {
                do {
                    list($message_code, $message_length, $message) = $this->get_message($client);
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
    private function get_message($client)
    {
        $binary_code = fread($client, 1);
        $message_code = current(unpack('a', $binary_code));
        if ($message_code === 'R') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = current(unpack('N', fread($client, $message_length - 4)));

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'S') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);
            $message = json_encode(explode("\0", trim($message)));

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'Z') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'K') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = current(unpack('N', fread($client, $message_length - 4)));

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'T') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'D') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);

            return [$message_code, $message_length, $message];
        } elseif ($message_code === 'C') {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);

            return [$message_code, $message_length, $message];
        } else {
            $message_length = current(unpack('N', fread($client, 4)));
            $message = fread($client, $message_length - 4);

            return [$message_code, $message_length, $message];
        }
    }
}
