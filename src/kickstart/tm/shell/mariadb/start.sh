if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify mariadb MARIADB_ROOT_PASSWORD as the second argument";
  exit 1;
fi
podman run --name $1  --replace -d -p 3306:3306 $3 \
  --env MARIADB_ROOT_PASSWORD=$2 \
  mariadb
#podman run --name $1-db -d -p 3306:3306 --mount type=bind,src=/opt/$1-project/mariadb,dst=/var/lib/mysql --env MARIADB_ROOT_PASSWORD=$2 mariadb