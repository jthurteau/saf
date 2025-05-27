# https://hub.docker.com/_/mariadb
if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify host install path as the second argument";
  exit 1;
fi
if [ -z "$3" ]; then
  echo "MUST specify mariadb MYSQL_ROOT_PASSWORD as the third argument";
  exit 1;
fi
# podman build /vagrant --network $1 -t mariadb -f /vagrant/$2/mariadb/Dockerfile --build-arg MARIADB_ROOT_PASSWORD=$3 && /vagrant/$2/shell/mariadb/start.sh mariadb $3
podman build /vagrant -t mariadb -f /vagrant/$2/mariadb/Dockerfile --build-arg MARIADB_ROOT_PASSWORD=$3 && /vagrant/$2/shell/mariadb/start.sh mariadb $3