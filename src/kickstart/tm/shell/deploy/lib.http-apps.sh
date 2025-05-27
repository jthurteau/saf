mkdir -p /var/www/application
mkdir -p /opt/application/vendor
mkdir -p /opt/application/vendor-for/$1
# create the normal storage directory
mkdir -p /var/www/storage/$1
chown www-data /var/www/storage/$1
# chmod o+w /var/www/storage/$1
# use /opt/project/storage/$1 instead...
