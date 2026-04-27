ARG PHP_VERSION=8.4
FROM dunglas/frankenphp:1.3-php${PHP_VERSION}-alpine AS php-franken

# Install system dependencies
# Note: Alpine uses 'apk'. 'unzip' and 'libcap' are essential for FrankenPHP operations.
RUN apk add sqlite git unzip zip libcap

# Build arguments
ARG DOCKER_ENV=production
ARG XDEBUG_VERSION=xdebug
ARG XDEBUG_PORT=9003
ARG XDEBUG_CLIENT_HOST=host.docker.internal

# Use the 'install-php-extensions' helper (pre-installed in the FrankenPHP image)
# pcntl is vital for FrankenPHP's worker mode.
RUN install-php-extensions pcntl opcache zip intl

# Conditional Dev/Production Logic
RUN if [ "$DOCKER_ENV" = "development" ]; then \
    install-php-extensions xdebug; \
    echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=${XDEBUG_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=${XDEBUG_CLIENT_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && { \
        echo "error_reporting = E_ALL"; \
        echo "display_startup_errors = On"; \
        echo "display_errors = On"; \
    } > /usr/local/etc/php/conf.d/error-reporting.ini; \
  else \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini; \
  fi

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Expose port HTTP & HTTPS
EXPOSE 443
EXPOSE 80
WORKDIR /var/www/html
# FrankenPHP Environment Defaults
# SERVER_NAME handles automatic HTTPS and Caddy routing.
ENV SERVER_NAME=https://localhost
ENV TZ=Asia/Jakarta
ENV SQLITE_DEFAULT_NAME=default.sqlite

COPY sql/ /docker-entrypoint-initdb.d/
COPY caddyfile /etc/caddy/Caddyfile
# Entrypoint setup
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
# FORCE CLEAN: Remove Windows line endings and ensure shebang is compatible
RUN chmod +x /usr/local/bin/entrypoint.sh

# Recommended: Give the binary permission to bind to low ports without root
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# CMD ["frankenphp", "php-server"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]