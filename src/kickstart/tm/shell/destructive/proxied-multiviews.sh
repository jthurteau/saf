# this script replaces /var/www/html, so it can be quite distructive if missused
if [ -z "$1" ]; then
  echo "MUST specify application name, e.g. /opt/application/<name> as the first argument";
  exit 1;
fi
#TODO support optional specific spot in /var/www/html for multiapp case
if [ -d "/var/www/html" ]; then
    rm -Rf /var/www/html; else
    rm /var/www/html;
fi
ln -s /opt/application/$1/public /var/www/html;
