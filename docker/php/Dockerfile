FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    git \
    unzip \
    sqlite-dev \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["tail", "-f", "/dev/null"]
