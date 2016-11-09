<?php

namespace Postgres;

interface Connection
{
    public function connect();
    public function startup();
}
