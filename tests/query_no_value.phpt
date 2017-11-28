--TEST--
querying no values
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
SELECT s.n AS n, '' AS e1, '' AS e2
FROM generate_series(1, 2) AS s(n)
SQL;

$rows = $conn->query($sql);
foreach ($rows as $row) {
    var_dump($row);
}

?>
--EXPECTF--
array(3) {
  ["n"]=>
  string(1) "1"
  ["e1"]=>
  string(0) ""
  ["e2"]=>
  string(0) ""
}
array(3) {
  ["n"]=>
  string(1) "2"
  ["e1"]=>
  string(0) ""
  ["e2"]=>
  string(0) ""
}
