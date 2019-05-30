FROM ubuntu:16.04

RUN \
    apt-get update && \
    apt-get -y --no-install-recommends install \
        libicu-dev \
        unzip \
        mc \
        build-essential \
        npm \
        wget \
        git \
        memcached redis-server \
        php php-fpm \
        php-pdo-mysql \
        php-pdo-pgsql \
        php-intl \
        php-xml \
        php-mbstring \
        php-mcrypt \
        php-bcmath \
        php-curl \
        cron \
        && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN sed -i -e "s/pid =.*/pid = \/var\/run\/php7.0-fpm.pid/" /etc/php/7.0/fpm/php-fpm.conf && \
    sed -i -e "s/error_log =.*/error_log = \/proc\/self\/fd\/2/" /etc/php/7.0/fpm/php-fpm.conf && \
    sed -i -e "s/;daemonize\s*=\s*yes/daemonize = no/g" /etc/php/7.0/fpm/php-fpm.conf && \
    sed -i "s/listen = .*/listen = 9000/" /etc/php/7.0/fpm/pool.d/www.conf

RUN npm install -g n && \
    n lts

EXPOSE 9000

