FROM ubuntu:22.04

# ARG NODE_VERSION=20

ARG APP_TIMEZONE= ${APP_TIMEZONE:-"Europe/Paris"}
ARG APP_PHP_SUPERVISOR_USER= ${APP_PHP_SUPERVISOR_USER:-"hephaestus"}

LABEL maintainer="Aizekyel"
LABEL name="hephaestus"

#------------------------------------------------------------------------------------------------------------------------
RUN echo $APP_PHP_SUPERVISOR_USER
RUN useradd $APP_PHP_SUPERVISOR_USER -U
WORKDIR /home/$APP_PHP_SUPERVISOR_USER/hephaestus
RUN ln -snf /usr/share/zoneinfo/$APP_TIMEZONE /etc/localtime && echo $APP_TIMEZONE  > /etc/timezone
#------------------------------------------------------------------------------------------------------------------------
RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 dnsutils librsvg2-bin fswatch ffmpeg nano  \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.3-cli php8.3-dev \
    php8.3-pgsql php8.3-sqlite3 php8.3-gd \
    php8.3-curl \
    php8.3-gmp \
    php8.3-imap php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-zip php8.3-bcmath php8.3-soap \
    php8.3-intl php8.3-readline \
    php8.3-ldap \
    php8.3-msgpack php8.3-igbinary php8.3-redis php8.3-swoole \
    php8.3-memcached php8.3-pcov php8.3-imagick php8.3-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    # && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    # && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    # && apt-get install -y nodejs \
    # && npm install -g npm \
    # && npm install -g pnpm \
    # && npm install -g bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /etc/apt/keyrings/yarn.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    # && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    # && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt jammy-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y yarn \
    && apt-get install -y mysql-client \
    # && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# RUN echo '[\[\e[92m\]\D{%d}\[\e[0m\]/\[\e[92m\]\D{%m}\[\e[0m\]/\[\e[32m\]\D{%Y}\[\e[0m\]-\[\e[92;1m\]\t\[\e[0m\]][\[\e[92;1m\]\u\[\e[0m\]@\[\e[96;1m\]\h\[\e[0m\]|\[\e[96m\]\l\[\e[0m\]][\[\e[92m\]\w\[\e[0m\]][$(git branch --show-current 2>/dev/null)]\$' >  /home/$APP_PHP_SUPERVISOR_USER/.bashrc


RUN echo 'export PS1="[\[\e[92m\]\D{%d}\[\e[0m\]/\[\e[92m\]\D{%m}\[\e[0m\]/\[\e[32m\]\D{%Y}\[\e[0m\]-\[\e[92;1m\]\D{%H:%M:%S}\[\e[0m\]][\[\e[92;1m\]\u\[\e[0m\]@\[\e[96;1m\]\h\[\e[0m\]|\[\e[96m\]\l\[\e[0m\]][\[\e[92m\]\w\[\e[0m\]][$(git branch --show-current 2>/dev/null)]\$ "' >> /etc/bash.bashrc

RUN chown -R $APP_PHP_SUPERVISOR_USER /home/$APP_PHP_SUPERVISOR_USER
USER $APP_PHP_SUPERVISOR_USER
