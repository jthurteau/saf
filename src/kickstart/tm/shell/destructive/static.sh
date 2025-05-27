# this script REMOVES /var/www/localhost/htdocs RECURSIVELY make sure it isn't a symlink to writable files on the host
rm -Rf /var/www/localhost/htdocs
ln -s /vagrant/${1:-public} /var/www/localhost/htdocs