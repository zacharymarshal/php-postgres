# php-postgres

[![Build Status](https://travis-ci.org/zacharyrankin/php-postgres.svg?branch=master)](https://travis-ci.org/zacharyrankin/php-postgres)

php-postgres is a pure php postgres client, designed to help developers understand what is happening when they send a query to Postgres.

How can I make developers badass?

### CLI Usage

```
bin/php-postgres play localhost --port=5432
# send startup message
> send LENGTH 3::int16 0::int16 "user" NUL "zacharyrankin" NUL "database" NUL "postgres" NUL NUL
# send startup message helper
> send_startup --user=zacharyrankin --database=dev_greendot
# send query message
> send Q::ident LENGTH "SELECT 1" NUL
# get 100 bytes from the stream
> get 100
# get the next message
> get_message
# get all the messages
> get_messages
```

### Constants

#### LENGTH

`LENGTH` will automatically prepend the length of the message and convert it to int32.  For example, these two messages are the same:

- `send Q::ident LENGTH "SELECT 1" NUL`
- `send Q::ident 13::int32 "SELECT 1" NUL`

#### NUL

`NUL` will send a NUL character [https://en.wikipedia.org/wiki/Null_character](https://en.wikipedia.org/wiki/Null_character), which in php can be represented as `\0`.
