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
            }
        } while ($user_input !== 'exit');
    }
}
