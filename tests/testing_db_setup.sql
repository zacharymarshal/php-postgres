DROP USER IF EXISTS testing_cleartext_user;
DROP DATABASE IF EXISTS testing_cleartext;
CREATE USER testing_cleartext_user WITH PASSWORD 'ASD123';
CREATE DATABASE testing_cleartext;
GRANT ALL PRIVILEGES ON DATABASE testing_cleartext TO testing_cleartext_user;
