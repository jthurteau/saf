# this script COPIES composer.lock from the container to the VM to the host
podman cp $(podman container list --filter ancestor="localhost/${2:-php-fpm}:latest" --format {{.Names}} --last 1):/tmp/composer.lock /opt/project/composer.lock \
&& cp /opt/project/composer.lock /vagrant/composer.lock