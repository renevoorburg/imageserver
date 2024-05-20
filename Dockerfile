FROM php:7.4-fpm
RUN apt-get update && apt-get install -y nginx libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick


COPY ./nginx/imageserver.conf /etc/nginx/sites-available/default
COPY ./nginx/fastcgi-php.conf /etc/nginx/snippets/fastcgi-php.conf

COPY . /var/www

RUN chown -R www-data:www-data /var/www

EXPOSE 80
CMD service nginx start && php-fpm
#CMD ["tail", "-f", "/dev/null"]