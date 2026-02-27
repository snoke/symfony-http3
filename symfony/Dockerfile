FROM php:8.4-cli
WORKDIR /app

RUN apt-get update && apt-get install -y git unzip curl && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json /app/composer.json
RUN composer install --no-interaction --prefer-dist

COPY . /app

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
