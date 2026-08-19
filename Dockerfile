FROM php:8.4-fpm-alpine

# Instalar dependências de sistema, Python3 e bibliotecas MSSQL
RUN apk add --no-cache \
    nginx \
    supervisor \
    python3 \
    py3-pip \
    py3-pymysql \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetds-dev \
    build-base \
    python3-dev \
    g++

RUN pip3 install --break-system-packages pymssql pymysql

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd intl pdo_dblib

# Configurações personalizadas do PHP (Aumentar limite de variáveis max_input_vars)
RUN echo "max_input_vars = 10000" > /usr/local/etc/php/conf.d/custom-limits.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/custom-limits.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/custom-limits.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/custom-limits.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configuração Nginx
COPY <<EOF /etc/nginx/http.d/default.conf
server {
    listen 8008;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/html/public;
    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
        gzip_static on;
    }
}
EOF

# Configuração Supervisor
COPY <<EOF /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
EOF

EXPOSE 8008

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
