if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
#rc-service nginx restart
podman restart $1