# this script REMOVES /var/www/localhost/htdocs RECURSIVELY make sure it isn't a symlink to writable files on the host
if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
# $2 may be set to a pattern match of files that should be removed (e.g. old-style *.php mix-in gateways)
mkdir -p /opt/tmp/${1}/static
rm -Rf /opt/tmp/${1}/static/*
cp -R /vagrant/public/* /opt/tmp/${1}/static
if [ -z "$2" ]; then
  rm -Rf /opt/tmp/${1}/static/${2}
fi
if [ -d "/var/www/localhost/htdocs" ]; then
    rm -Rf /var/www/localhost/htdocs;
    ln -s /opt/tmp/${1}/static /var/www/localhost/htdocs;
fi