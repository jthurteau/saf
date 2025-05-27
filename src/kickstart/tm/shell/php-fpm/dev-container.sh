# https://hub.docker.com/_/php
if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify host install path as the second argument";
  exit 1;
fi
echo building $1
#podman build -t php-fpm /vagrant/$2 --build-arg PRIMARY_APPLICATION=$1 && /vagrant/$2/shell/php-fpm/start.sh $1 $2
podman build /vagrant -t php-fpm --target dev --skip-unused-stages --build-arg PRIMARY_APPLICATION=$1 && /vagrant/$2/shell/php-fpm/start.sh $1 $3