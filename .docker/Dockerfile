ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-apache-bullseye AS php-apache


# # Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
  sqlite3 git\
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Define a build argument
ARG DOCKER_ENV
ARG XDEBUG_VERSION
ARG XDEBUG_PORT
ARG XDEBUG_CLIENT_HOST
# Use the build argument in a RUN instruction
RUN if [ "$DOCKER_ENV" = "development" ]; then \
  # Commands to run if ENV is development
    yes | pecl install ${XDEBUG_VERSION} && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear; \
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=${XDEBUG_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=${XDEBUG_CLIENT_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
  else \
    # Commands to run if ENV is not development
    echo "Not running in development mode"; \
    # Disable Apache's ServerSignature and limit ServerTokens
    echo "ServerSignature Off" >> /etc/apache2/conf-available/security.conf \
    && echo "ServerTokens Prod" >> /etc/apache2/conf-available/security.conf \
    # Disable the X-Powered-By header in PHP
    echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini;\
  fi

# Clean
RUN apt-get autoremove --purge -y && apt-get autoclean -y && apt-get clean -y \
  && rm -rf /var/lib/apt/lists/* \
  && rm -rf /tmp/* /var/tmp/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Generate self-signed cert
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/server.key \
    -out /etc/apache2/ssl/server.crt \
    -subj "/C=ID/ST=Jakarta/L=Jakarta/O=Dev/OU=Dev/CN=localhost"

# Add SSL vhost config
COPY apache.conf /etc/apache2/sites-available/default-ssl.conf

# Enable site
RUN a2ensite default-ssl.conf

# Expose port HTTP & HTTPS
EXPOSE 443
EXPOSE 80

# Enable mod_rewrite
RUN a2enmod rewrite headers remoteip env ssl



ENV TZ=Asia/Jakarta
ENV SQLITE_DEFAULT_NAME=default.sqlite
COPY sql/ /docker-entrypoint-initdb.d/
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# WORKDIR /var/www/html
# RUN composer install

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]