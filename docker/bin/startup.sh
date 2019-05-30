#!/usr/bin/env bash

crontab /app/docker/crontab
service cron start

service redis-server start

/usr/bin/env php /app/yii background/redo

php-fpm7.0
