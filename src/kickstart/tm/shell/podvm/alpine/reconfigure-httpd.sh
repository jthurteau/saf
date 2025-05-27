if [ -z "$1" ]; then
  echo "MUST specify host build path as the first argument";
  exit 1;
fi
/vagrant/$1/shell/podvm/nginx/setup.sh $1 $2
#cp /vagrant/$1/nginx/backend /etc/nginx/extra/backend.conf
rc-service nginx restart