# Dockerfile
FROM php:8.2-cli

# 1) accept host UID/GID with sane defaults
ARG UID=1000
ARG GID=1000

# 2) install PHP + zip
RUN apt-get update \
 && apt-get install -y git unzip zip libzip-dev \
 && docker-php-ext-install zip

# 3) create a container user matching your host
RUN groupadd --gid ${GID} appuser \
 && useradd  --uid ${UID} --gid ${GID} --create-home --shell /bin/bash appuser

# 4) bring in Composer v2
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# 5) switch to that user & set workdir
USER appuser
WORKDIR /app

# 6) serve via built-in PHP server
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
