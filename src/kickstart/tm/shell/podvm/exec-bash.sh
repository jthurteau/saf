if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  u=""; else
  u="--user $2 ";
fi
podman exec --interactive --tty $u \
  $1 /bin/sh