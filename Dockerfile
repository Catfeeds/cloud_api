FROM registry.cn-beijing.aliyuncs.com/wa/php-fpm:1.0.3

MAINTAINER Chuanjian Wang <chuanjian@funxdata.com>

WORKDIR /var/www/html

ENV APPLICATION_ENV=development

ADD application /var/www/html/application
ADD public /var/www/html/public
ADD system /var/www/html/system
ADD composer.json /var/www/html/composer.json

ENV SKIP_COMPOSER=true
RUN cd /var/www/html/application ;\
    composer dump-autoload --optimize

ADD hack/nginx.conf /etc/nginx/nginx.conf
ADD hack/start.sh /start.sh

CMD ["/start.sh"]
