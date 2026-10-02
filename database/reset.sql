DROP DATABASE IF EXISTS itassets;
CREATE DATABASE IF NOT EXISTS itassets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE itassets;
SOURCE c:/xampp/htdocs/itassets/database/schema.sql;
SOURCE c:/xampp/htdocs/itassets/database/seed.sql;
