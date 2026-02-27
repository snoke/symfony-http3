FROM php:8.4-cli
WORKDIR /app

RUN apt-get update && apt-get install -y git unzip curl && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock /app/
COPY bin /app/bin
COPY config /app/config
COPY public /app/public
COPY src /app/src
COPY templates /app/templates
COPY .env .env.dev /app/

RUN composer install --no-interaction --prefer-dist

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
