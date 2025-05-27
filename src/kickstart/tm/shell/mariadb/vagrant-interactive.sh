if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
podman run -it --mount type=bind,src=/opt/{$1}-project/mariadb,dst=/var/lib/mysql mariadb /bin/sh