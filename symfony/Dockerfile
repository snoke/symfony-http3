FROM php:8.4-cli
WORKDIR /app

RUN apt-get update && apt-get install -y git unzip curl && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The build context is the repo root. Copy the Symfony app into /app and the bundle path-repo into /bundle.
COPY symfony/composer.json symfony/composer.lock symfony/symfony.lock /app/
COPY symfony/bin /app/bin
COPY symfony/config /app/config
COPY symfony/public /app/public
COPY symfony/src /app/src
COPY symfony/templates /app/templates
COPY symfony/.env symfony/.env.dev /app/

COPY bundle /bundle

RUN composer install --no-interaction --prefer-dist

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
