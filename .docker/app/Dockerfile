# -----------------------------
# 1) Base comum
# -----------------------------
FROM ubuntu:24.04 AS base

LABEL maintainer="Davidson Alencar"

ARG WWWGROUP

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# APT tweaks (iguais aos seus)
RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

# Pacotes básicos comuns
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && \
#    apt-get upgrade -y && \
    mkdir -p /etc/apt/keyrings && \
    apt-get install -y --no-install-recommends \
    gnupg  \
    curl  \
    ca-certificates  \
    libpng-dev  \
    gosu  \
#    zip  \
#    unzip  \
#    git  \
#    sqlite3 \
#    python3  \
#    dnsutils  \
#    libcap2-bin  \
#    librsvg2-bin  \
#    fswatch  \
#    ffmpeg  \
#    nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Usuário/grupo
RUN userdel -r ubuntu || true
#RUN groupadd --force -g ${WWWGROUP} sail
#RUN useradd -ms /bin/bash --no-user-group -g ${WWWGROUP} -u 1337 sail
# RUN git config --global --add safe.directory /var/www/html


# -----------------------------
# 2) PHP Runtime
# -----------------------------
FROM base AS php-runtime

# Repo do Ondrej (PHP)
RUN curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' \
    | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null && \
    echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
    > /etc/apt/sources.list.d/ppa_ondrej_php.list

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    libgd3 \
    php8.5-cli \
#    php8.5-pgsql \
#    php8.5-sqlite3 \
    php8.5-gd \
    php8.5-curl \
#    php8.5-mongodb \
#    php8.5-imap \
    php8.5-mysql \
    php8.5-mbstring \
    php8.5-xml \
    php8.5-zip \
    php8.5-bcmath \
#    php8.5-soap \
    php8.5-intl \
    php8.5-readline \
#    php8.5-ldap \
    php8.5-msgpack \
    php8.5-igbinary \
    php8.5-redis \
#    php8.5-memcached \
    php8.5-imagick \
    php8.5-fpm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/^;*daemonize\s*=.*/daemonize = no/' /etc/php/8.5/fpm/php-fpm.conf
RUN sed -i 's/^;*listen\s*=.*/listen = 0.0.0.0:9000/' /etc/php/8.5/fpm/pool.d/www.conf

COPY ./.docker/app/php.ini /etc/php/8.5/cli/conf.d/99-app.ini
COPY ./.docker/app/php.ini /etc/php/8.5/fpm/conf.d/99-app.ini

COPY ./.docker/app/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container


# -----------------------------
# 3) DB Clients
# -----------------------------
FROM base AS db-clients
ARG MYSQL_CLIENT="mysql-client"
#ARG POSTGRES_VERSION=18

#RUN curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc \
#    | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null && \
#    echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" \
#    > /etc/apt/sources.list.d/pgdg.list

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    ${MYSQL_CLIENT} \
#    postgresql-client-${POSTGRES_VERSION} \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# -----------------------------
# 4) Node runtime
# -----------------------------
FROM base AS node-runtime
ARG NODE_VERSION=24

RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_VERSION}.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# -----------------------------
# 5) Composer dependences
# -----------------------------
FROM php-runtime AS composer-deps

RUN curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-ansi \
    --no-progress \
    --ignore-platform-req=ext-xdebug


# -----------------------------
# 6) Build de assets (Vite / Mix / etc)
# -----------------------------
FROM php-runtime AS node-build

WORKDIR /var/www/html

# 1) Dependências PHP (para o artisan funcionar)
COPY --from=composer-deps /var/www/html/vendor ./vendor

# 2) NODE libs
COPY --from=node-runtime /usr/bin/node /usr/bin/node
COPY --from=node-runtime /usr/lib/node_modules /usr/lib/node_modules
RUN ln -sf /usr/lib/node_modules/npm/bin/npm-cli.js /usr/bin/npm || true

# 3) Dependências do Node
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN --mount=type=cache,target=/root/.npm,sharing=locked \
    if [ -f package-lock.json ]; then \
      npm ci; \
    elif [ -f yarn.lock ]; then \
      yarn install --frozen-lockfile; \
    else \
      npm install; \
    fi

# 4) Código da aplicação
COPY . .

# 5) Agora o Vite pode rodar o Wayfinder com artisan
RUN npm run build



# -----------------------------
# 7) Production final (enxuto)
# -----------------------------
FROM php-runtime AS production

# DB Clients
COPY --from=db-clients /usr/bin/mysql /usr/bin/mysql
#COPY --from=db-clients /usr/bin/psql /usr/bin/psql

# Copia projeto
COPY . .
COPY --from=composer-deps /var/www/html/vendor ./vendor
COPY --from=node-build /var/www/html/public/build ./public/build
RUN rm -f ./public/hot
RUN rm -f ./public/*.hot

RUN php artisan package:discover --ansi

EXPOSE 9000/tcp
ENTRYPOINT ["start-container"]
CMD ["php-fpm8.5", "-F"]


# -----------------------------
# 8) Development (dev extras)
# -----------------------------
FROM php-runtime AS development

# Dev-only: xdebug, pcov, dev headers etc
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    php8.5-dev \
    php8.5-xdebug \
    php8.5-pcov \
    netcat-traditional \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY ./.docker/app/xdebug.ini /etc/php/8.5/cli/conf.d/99-xdebug.ini
COPY ./.docker/app/xdebug.ini /etc/php/8.5/fpm/conf.d/99-xdebug.ini

# DB Clients
COPY --from=db-clients /usr/bin/mysql /usr/bin/mysql
#COPY --from=db-clients /usr/bin/psql /usr/bin/psql

# Libs
COPY --from=composer-deps /usr/bin/composer /usr/bin/composer
COPY --from=node-runtime /usr/bin/node /usr/bin/node
COPY --from=node-runtime /usr/lib/node_modules /usr/lib/node_modules
RUN ln -sf /usr/lib/node_modules/npm/bin/npm-cli.js /usr/bin/npm || true

EXPOSE 9000/tcp
ENTRYPOINT ["start-container"]
CMD ["php-fpm8.5", "-F"]


# -----------------------------
# 9) Playwright deps (somente testes/e2e)
# -----------------------------
FROM development AS playwright
ENV PLAYWRIGHT_BROWSERS_PATH=0

RUN npx playwright install-deps

