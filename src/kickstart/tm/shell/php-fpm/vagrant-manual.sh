if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  u=""; else
  u="--user $2 ";
fi
podman run -it --mount type=bind,src=/vagrant,dst=/opt/application/$1,ro=true --mount type=bind,src=/opt/project,dst=/opt/project --mount type=bind,src=/opt/application/vendor,dst=/opt/application/vendor,ro=true $u php-fpm /bin/sh
## sample for a bare docker/podman run
# podman run -it --mount type=bind,src=$PROJECT_ROOT,dst=/opt/application/$1,ro=true [--mount type=bind,src=~/.tm/project-$1,dst=/opt/project] $u php-fpm /bin/sh