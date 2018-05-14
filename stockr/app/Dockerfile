FROM php:7.2.5-apache

# Install Extensions
#RUN apt-get update && apt-get install -y \
#       [package-name] \
#       [package-name] \
#   && docker-php-ext-configure [lib-name] [options] \
#   && docker-php-ext-install -j$(nproc) [lib-name]

# Enable apache2 rewrite
RUN a2enmod rewrite

# Copy custom Apache config
COPY config/apache2.conf /etc/apache2/apache2.conf
# COPY config/000-default.conf /etc/apache2/sites-available/000-default.conf
# COPY config/php.ini /usr/local/etc/php/

# Copy files
COPY src/ /var/www/html/

# Restart apache
RUN service apache2 restart
