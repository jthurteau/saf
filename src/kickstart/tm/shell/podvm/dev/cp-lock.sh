if [ -z "$1" ]; then
  echo "MUST specify the container name as the first argument";
  exit 1;
fi
podman cp $1:/tmp/composer.lock /vagrant/composer.lock