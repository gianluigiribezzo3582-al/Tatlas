# PostgreSQL 16 + PostGIS 3.4 + pgvector 0.8
# pgvector is copied from its official image (no apt needed at build time).
FROM pgvector/pgvector:0.8.0-pg16 AS pgvector

FROM postgis/postgis:16-3.4
COPY --from=pgvector /usr/lib/postgresql/16/lib/vector.so /usr/lib/postgresql/16/lib/
COPY --from=pgvector /usr/share/postgresql/16/extension/vector* /usr/share/postgresql/16/extension/
COPY init/ /docker-entrypoint-initdb.d/
