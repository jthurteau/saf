if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify db flavor (service name) as the second argument";
  exit 1;
fi
podman restart $2