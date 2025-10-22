# ===== True Async PHP with PHP-FPM + Nginx (Linux x64 Release) =====
# Multi-stage build: builder + runtime with PHP-FPM and Nginx

# ---------- BUILDER STAGE ----------
FROM ubuntu:24.04 AS builder

# ---------- 1. System toolchain & libraries ----------
RUN apt-get update && apt-get install -y \
    autoconf bison build-essential curl re2c git \
    cmake ninja-build wget dos2unix \
    libxml2-dev libssl-dev pkg-config libargon2-dev \
    libcurl4-openssl-dev libedit-dev libreadline-dev \
    libsodium-dev libsqlite3-dev libonig-dev libzip-dev \
    libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
    libgmp-dev libldap2-dev libsasl2-dev libpq-dev \
    libmysqlclient-dev libbz2-dev libenchant-2-dev \
    libffi-dev libgdbm-dev liblmdb-dev libsnmp-dev \
    libtidy-dev libxslt1-dev libicu-dev libpsl-dev

# ---------- 2. libuv 1.49 ----------
RUN wget -q https://github.com/libuv/libuv/archive/v1.49.0.tar.gz \
 && tar -xf v1.49.0.tar.gz \
 && cd libuv-1.49.0 && mkdir build && cd build \
 && cmake .. -G Ninja -DCMAKE_BUILD_TYPE=Release -DBUILD_TESTING=OFF \
 && ninja && ninja install && ldconfig \
 && cd / && rm -rf libuv*

# ---------- 3. curl 8.10 ----------
RUN wget -q https://github.com/curl/curl/releases/download/curl-8_10_1/curl-8.10.1.tar.gz \
 && tar -xf curl-8.10.1.tar.gz \
 && cd curl-8.10.1 \
 && ./configure --prefix=/usr/local --with-openssl --enable-shared --disable-static \
 && make -j$(nproc) && make install && ldconfig \
 && cd / && rm -rf curl*

# ---------- 4. PHP sources ----------
RUN git clone --depth=1 --branch=true-async-stable https://github.com/true-async/php-src /usr/src/php-src

# ---------- 5. Clone async extension ----------
RUN git clone --depth=1 https://github.com/true-async/php-async /usr/src/php-src/ext/async

# ---------- 5.1. Fix Windows line endings in config.m4 ----------
RUN dos2unix /usr/src/php-src/ext/async/config.m4

WORKDIR /usr/src/php-src

# ---------- 6. Configure & build PHP with FPM ----------
RUN ./buildconf --force && \
    ./configure \
        --prefix=/usr/local \
        --with-pdo-mysql=mysqlnd --with-mysqli=mysqlnd \
        --with-pgsql --with-pdo-pgsql --with-pdo-sqlite \
        --without-pear \
        --enable-gd --with-jpeg --with-webp --with-freetype \
        --enable-exif --with-zip --with-zlib \
        --enable-soap --enable-xmlreader --with-xsl --with-tidy --with-libxml \
        --enable-sysvsem --enable-sysvshm --enable-shmop --enable-pcntl \
        --with-readline \
        --enable-mbstring --with-curl --with-gettext \
        --enable-sockets --with-bz2 --with-openssl --with-gmp \
        --enable-bcmath --enable-calendar --enable-ftp --with-enchant \
        --enable-sysvmsg --with-ffi --enable-dba --with-lmdb --with-gdbm \
        --with-snmp --enable-intl --with-ldap --with-ldap-sasl \
        --enable-werror \
        --with-config-file-path=/etc --with-config-file-scan-dir=/etc/php.d \
        --disable-debug \
        --enable-fpm --with-fpm-user=www-data --with-fpm-group=www-data \
        --enable-async && \
    make -j$(nproc) && make install

# ---------- 7. Configure PHP-FPM ----------
RUN mkdir -p /etc/php.d /var/log/php-fpm /run/php-fpm && \
    echo "opcache.enable=1" > /etc/php.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /etc/php.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=8" >> /etc/php.d/opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /etc/php.d/opcache.ini && \
    echo "opcache.revalidate_freq=2" >> /etc/php.d/opcache.ini

# ---------- RUNTIME STAGE ----------
FROM ubuntu:24.04 AS runtime

# Install runtime dependencies + nginx
RUN apt-get update && apt-get install -y --no-install-recommends \
    libxml2 libssl3 libargon2-1 \
    libcurl4 libedit2 libreadline8 \
    libsodium23 libsqlite3-0 libonig5 libzip4 \
    libpng16-16 libjpeg8 libwebp7 libfreetype6 \
    libgmp10 libldap2 libsasl2-2 libpq5 \
    libmysqlclient21 libbz2-1.0 libenchant-2-2 \
    libffi8 libgdbm6 liblmdb0 libsnmp40 \
    libtidy5deb1 libxslt1.1 libicu74 libpsl5 \
    ca-certificates nginx supervisor \
    && rm -rf /var/lib/apt/lists/*

# Copy built PHP and libraries from builder stage
COPY --from=builder /usr/local /usr/local
COPY --from=builder /etc/php.d /etc/php.d
COPY --from=builder /var/log/php-fpm /var/log/php-fpm
COPY --from=builder /run/php-fpm /run/php-fpm

# Update library cache
RUN ldconfig

# Set PATH
ENV PATH="/usr/local/bin:/usr/local/sbin:$PATH"

# Create www-data user directories
RUN mkdir -p /var/www/html && \
    chown -R www-data:www-data /var/www/html /var/log/php-fpm /run/php-fpm

# Copy configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.conf
COPY www.conf /usr/local/etc/php-fpm.d/www.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create a simple test file
RUN echo '<?php phpinfo(); ?>' > /var/www/html/index.php && \
    echo '<?php echo "TrueAsync works!\\n"; var_dump(extension_loaded("async")); ?>' > /var/www/html/test.php && \
    chown -R www-data:www-data /var/www/html

# Verify PHP installation
RUN php -v && php -m | grep async

WORKDIR /var/www/html

# Expose ports
EXPOSE 80

# Start supervisor to manage nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
