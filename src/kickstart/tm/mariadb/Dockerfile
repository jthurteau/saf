##
# MariaDB Dockerfile # https://hub.docker.com/_/mariadb
# for ncsulibraries - jthurtea
# development container that mounts the repo

FROM mariadb:10.11 as builder 

ARG MARIADB_ROOT_PASSWORD
ENV MYSQL_DATABASE=rooms_ems_test
ENV MYSQL_USER=user
ENV MYSQL_PASSWORD=${MARIADB_ROOT_PASSWORD}
ENV MYSQL_ROOT_PASSWORD=${MARIADB_ROOT_PASSWORD}

# run build from the root of the repo
# COPY config/sql/dev/* /docker-entrypoint-initdb.d
COPY config/sql/rooms.schema.sql /docker-entrypoint-initdb.d/001.db_schema.sql
COPY config/sql/rooms.data.room.migration.sql /docker-entrypoint-initdb.d/020.room_migration.sql

EXPOSE 3306

