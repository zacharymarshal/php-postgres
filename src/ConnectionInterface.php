<?php

namespace Postgres;

interface ConnectionInterface
{
    public function connect();
    public function startup();
    public function write(string $msg);
    public function read(int $len): string;
    public function readMessage(): array;
    public function readByte(): string;
    public function readInt32(): int;
}
