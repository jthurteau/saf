# this script REMOVES /var/www/localhost/htdocs RECURSIVELY make sure it isn't a symlink to writable files on the host
if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
if [ -d "/var/www/localhost/htdocs" ]; then
    rm -Rf /var/www/localhost/htdocs;
    ln -s /vagrant/public /var/www/localhost/htdocs;
fi