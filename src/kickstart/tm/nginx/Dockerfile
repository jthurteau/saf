FROM nginx:latest AS builder

COPY ./public /var/www/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

FROM builder AS php-fpm-nginx

COPY ./src/install/nginx/php-fpm-docker.conf /etc/nginx/conf.d/default.conf

FROM builder AS python-django-nginx

COPY ./src/install/nginx/python-django-docker.conf /etc/nginx/conf.d/default.conf