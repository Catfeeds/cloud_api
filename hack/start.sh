#!/bin/bash

set -ex;
# Set custom webroot
if [ ! -z "$APPLICATION_ENV" ]; then
 sed -i "s?development?${APPLICATION_ENV}?g" /etc/nginx/nginx.conf;
fi

# Prevent config files from being filled to infinity by force of stop and restart the container
lastlinephpconf="$(grep "." /etc/php7/php-fpm.conf | tail -1)"
if [[ $lastlinephpconf == *"php_flag[display_errors]"* ]]; then
 sed -i '$ d' /etc/php7/php-fpm.conf
fi

# Display PHP error's or not
if [[ "$ERRORS" != "1" ]] ; then
 echo php_flag[display_errors] = off >> /etc/php7/php-fpm.conf
else
 echo php_flag[display_errors] = on >> /etc/php7/php-fpm.conf
fi

# Display Version Details or not
if [[ "$HIDE_NGINX_HEADERS" == "0" ]] ; then
 sed -i "s/server_tokens off;/server_tokens on;/g" /etc/nginx/nginx.conf
else
 sed -i "s/expose_php = On/expose_php = Off/g" /etc/php7/php-fpm.conf
fi

# Increase the memory_limit
if [ ! -z "$PHP_MEM_LIMIT" ]; then
 sed -i "s/memory_limit = 128M/memory_limit = ${PHP_MEM_LIMIT}M/g" /etc/php7/php/conf.d/docker-vars.ini
fi

# Increase the post_max_size
if [ ! -z "$PHP_POST_MAX_SIZE" ]; then
 sed -i "s/post_max_size = 100M/post_max_size = ${PHP_POST_MAX_SIZE}M/g" /etc/php7/php/conf.d/docker-vars.ini
fi

# Increase the upload_max_filesize
if [ ! -z "$PHP_UPLOAD_MAX_FILESIZE" ]; then
 sed -i "s/upload_max_filesize = 100M/upload_max_filesize= ${PHP_UPLOAD_MAX_FILESIZE}M/g" /etc/php7/php/conf.d/docker-vars.ini
fi

# Enable xdebug
XdebugFile='/etc/php7/php/conf.d/docker-php-ext-xdebug.ini'
if [[ "$ENABLE_XDEBUG" == "1" ]] ; then
  if [ -f $XdebugFile ]; then
  	echo "Xdebug enabled"
  else
  	echo "Enabling xdebug"
  	echo "If you get this error, you can safely ignore it: /usr/local/bin/docker-php-ext-enable: line 83: nm: not found"
  	# see https://github.com/docker-library/php/pull/420
    docker-php-ext-enable xdebug
    # see if file exists
    if [ -f $XdebugFile ]; then
      # See if file contains xdebug text.
      if grep -q xdebug.remote_enable "$XdebugFile"; then
        echo "Xdebug already enabled... skipping"
      else
        echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > $XdebugFile # Note, single arrow to overwrite file.
        echo "xdebug.remote_enable=1 "  >> $XdebugFile
        echo "xdebug.remote_log=/tmp/xdebug.log"  >> $XdebugFile
        echo "xdebug.remote_autostart=false "  >> $XdebugFile # I use the xdebug chrome extension instead of using autostart
        # NOTE: xdebug.remote_host is not needed here if you set an environment variable in docker-compose like so `- XDEBUG_CONFIG=remote_host=192.168.111.27`.
        #       you also need to set an env var `- PHP_IDE_CONFIG=serverName=docker`
      fi
    fi
  fi
else
  if [ -f $XdebugFile ]; then
      echo "Disabling Xdebug"
    rm $XdebugFile
  fi
fi

if [ ! -z "$PUID" ]; then
  if [ -z "$PGID" ]; then
    PGID=${PUID}
  fi
  deluser nginx
  addgroup -g ${PGID} nginx
  adduser -D -S -h /var/cache/nginx -s /sbin/nologin -G nginx -u ${PUID} nginx
else
  if [ -z "$SKIP_CHOWN" ]; then
    chown -Rf nginx.nginx /var/www/html
  fi
fi

if [ -z "$SKIP_COMPOSER" ]; then
    # Try auto install for composer
    if [ -f "/var/www/html/composer.lock" ]; then
      composer dump-autoload --optimize
    fi
fi

# Start supervisord and services
exec /usr/bin/supervisord -n -c /etc/supervisord.conf

