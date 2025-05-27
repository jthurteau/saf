if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
#  --mount type=bind,src=/vagrant,dst=/opt/application/$1,ro=true \
#  --mount type=bind,src=/opt/application/vendor,dst=/opt/application/vendor,ro=true \
podman run --name $1 --replace -d -p 8090:9000 $2 \
  --mount type=bind,src=/vagrant,dst=/opt/application/$1,chown=true \
  --mount type=bind,src=/opt/project/storage/$1,dst=/opt/project/storage/$1,chown=true \
  --mount type=bind,src=/opt/application/vendor,dst=/opt/application/vendor \
  --env-file /vagrant/local-dev.dev-env \
  php-fpm
# podman network connect $1 $1
##"host.docker.internal:host-gateway"
## sample for a bare docker/podman run
# podman run -d -p 8090:9000 --mount type=bind,src=$PROJECT_ROOT,dst=/opt/application/$1,ro=true [--mount type=bind,src=~/.tm/project-$1,dst=/opt/project] php-fpm