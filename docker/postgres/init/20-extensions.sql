-- Enabled on the default DB and on template1, so test databases inherit them.
\connect template1
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS vector;
\connect tatlas
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS vector;
CREATE DATABASE tatlas_test TEMPLATE template1;
