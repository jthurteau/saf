#!/bin/sh
#podman exec --user www-data --workdir /opt/application/$1 $2 ./src/install/shell/composer/composer-update.sh && podman cp $1:/opt/application/$1/composer.lock /opt/project/composer.lock
podman exec --user www-data --workdir /opt/application/$1 $2 ./src/install/shell/composer/update-export.sh