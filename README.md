# php-postgres

[![Build Status](https://travis-ci.org/zacharyrankin/php-postgres.svg?branch=master)](https://travis-ci.org/zacharyrankin/php-postgres)

php-postgres is a pure php postgres client designed to help developers understand what is happening when they send a query to Postgres.

## Usage

### Examples

Example #1 create connection to Postgres backend.

```php
try {
    $conn = new Postgres\Connection('tcp://user:pass@localhost:5432/testdb?connect_timeout=2');
    $conn->connect();
} catch (PostgresException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### Parameters

Parameters are set via the connection url query string (`?connect_timeout=2&application_name=php-postgres`)

***

`connect_timeout` int (seconds)

Sets the amount of seconds to wait before timing out when connecting to the Postgres backend.

## CLI Usage

```
bin/php-postgres play localhost --port=5432
# send startup message
> send LENGTH 3::int16 0::int16 "user" NUL "zacharyrankin" NUL "database" NUL "postgres" NUL NUL
# send startup message helper
> send_startup --user=zacharyrankin --database=postgres
# send query message
> send Q::ident LENGTH "SELECT 1" NUL
# get 100 bytes from the stream
> get 100
# get the next message
> get_message
# get all the messages
> get_messages
```

## Message DSL (Domain Specific Language)

### Constants

#### LENGTH

`LENGTH` will automatically prepend the length of the message and convert it to int32.  For example, these two messages are the same:

- `send Q::ident LENGTH "SELECT 1" NUL`
- `send Q::ident 13::int32 "SELECT 1" NUL`

#### NUL

`NUL` will send a NUL character [https://en.wikipedia.org/wiki/Null_character](https://en.wikipedia.org/wiki/Null_character), which in php can be represented as `\0`.
