--TEST--
queries throw nice errors
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

$conn->query("BEGIN");
$sql = <<<SQL
SELECT *
FROM non_existent_table
SQL;

try {
    $rows = $conn->query($sql);
    if ($rows === false) {
        $err = $conn->getLastError();
        $conn->query("ROLLBACK");
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>
--EXPECTF--
string(%d) "Error running query"
