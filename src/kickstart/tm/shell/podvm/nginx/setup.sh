if [ -z "$1" ]; then
  echo "MUST specify host build path as the first argument";
  exit 1;
fi
cp /vagrant/$1/nginx/${2:-generic}-vagrant.conf /etc/nginx/http.d/default.conf
mkdir /var/www/error
cp /vagrant/$1/nginx/404.html /var/www/error/404.html
cp /vagrant/$1/nginx/${2:-generic}-50x.html /var/www/error/50x.html
cp /vagrant/$1/nginx/test.html /var/www/localhost/htdocs/test.html
chown -R :www-data /var/www/error