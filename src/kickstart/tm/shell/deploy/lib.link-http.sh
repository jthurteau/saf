ln -s /opt/application/$1 /var/www/application/$1
if [ -n "$3" ]; then
    ln -s /var/www/application/$1/$2 /var/www/html/$3; else
    rmdir /var/www/html;
    ln -s /var/www/application/$1/$2 /var/www/html;
fi
