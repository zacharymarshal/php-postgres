--TEST--
connect to local postgres.
--ENV--
return <<<END
DB_URL=postgres://php_postgres_no_passwd:@localhost:5432/php_postgres_testing?application_name=php-postgres-testing
DB_CONNECT_TIMEOUT=1
END;
--FILE--
<?php

require __DIR__ . '/../vendor/autoload.php';

$conn = new Postgres\Connection(getenv('DB_URL'), getenv('DB_CONNECT_TIMEOUT'));
$conn->connect();

$sql = <<<SQL
SELECT *
FROM pg_stat_activity
WHERE application_name='php-postgres-testing'
SQL;

$rows = $conn->query($sql);
foreach ($rows as $row) {
    var_dump($row);
}

?>
--EXPECTF--
array(1) {
  ["cnt"]=>
  string(1) "1"
}
