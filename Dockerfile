FROM php:8.2-fpm
RUN apt-get update && apt-get install -y nginx libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick

# PECL broken in PHP 8.3
#RUN apk add git --update --no-cache && \
#    git clone https://github.com/Imagick/imagick.git --depth 1 /tmp/imagick && \
#    cd /tmp/imagick && \
#    git fetch origin master && \
#    git switch master && \
#    cd /tmp/imagick && \
#    phpize && \
#    ./configure && \
#    make && \
#    make install && \
#    apk del git && \
#    docker-php-ext-enable imagick \

COPY ./nginx/imageserver.conf /etc/nginx/sites-available/default
COPY ./nginx/fastcgi-php.conf /etc/nginx/snippets/fastcgi-php.conf

COPY . /var/www
RUN chown -R www-data:www-data /var/www

EXPOSE 80
CMD service nginx start && php-fpm
