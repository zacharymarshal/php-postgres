<?php

namespace Postgres;

interface ConnectionInterface
{
    public function connect();
    public function startup();
}
