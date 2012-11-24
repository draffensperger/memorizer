CREATE DATABASE `memorizer`;

CREATE USER `memorizer_user` IDENTIFIED BY 'memorize';

GRANT SELECT, INSERT, UPDATE, DELETE ON memorizer.* TO `memorizer_user`;
