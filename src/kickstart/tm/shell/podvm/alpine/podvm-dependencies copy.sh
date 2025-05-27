if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify host build path as the second argument";
  exit 1;
fi
apk add bash
apk add coreutils
apk add curl
apk add nano
apk add nginx
# https://wiki.alpinelinux.org/wiki/Nginx
adduser -D -g 'www' www
/vagrant/$2/shell/podvm/nginx/setup.sh $2 ${3:-generic}
ln -s /vagrant/$2/projects/local-dev.$1 /opt/project
rc-service nginx start
rc-update add nginx default #this is supposed to autostart nginx?, but since it doesn't (seem to) we also `always` run restart.sh
apk add podman
# https://wiki.alpinelinux.org/wiki/Podman
rc-update add cgroups
rc-service cgroups start