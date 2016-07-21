# php-postgres

php-postgres is a pure php postgres client, designed to help developers understand what is happening when they send a query to Postgres.

How can I make developers badass?

### CLI

```
bin/php-postgres localhost --port=5432
# send a message
> send 42::int32 3::int16 0::int16 user\0zacharyrankin\0database\0dev_greendot\0\0
# send startup
> send_startup --user=zacharyrankin --database=dev_greendot
# send query
> send Q 13::int32 "SELECT 1"::string
> send_query "SELECT 1"::string
# get 100 bytes from the stream
> get 100
# get message
> get_message --raw
R (length 123)
Blah blah blah
> get_messages
R (length 123)
Blah blah blah
C (length 123)
Blah blah blah
```
