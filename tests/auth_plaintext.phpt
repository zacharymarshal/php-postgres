--TEST--
authenticate with postgres using plaintext password
--ENV--
return <<<END
DB_URL=postgres://php_postgres_plaintext_passwd:secret@localhost:5432/php_postgres_testing?application_name=php-postgres-testing
DB_CONNECT_TIMEOUT=1
END;
--FILE--
<?php

require __DIR__ . '/../vendor/autoload.php';

$conn = new Postgres\Connection(getenv('DB_URL'), getenv('DB_CONNECT_TIMEOUT'));
$conn->connect();

$sql = <<<SQL
SELECT 1
SQL;

$rows = $conn->query($sql);
var_dump($rows);

?>
--EXPECT--
array(1) {
  [0]=>
  array(1) {
    ["?column?"]=>
    string(1) "1"
  }
}
