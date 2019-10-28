FROM php:7.2.5-apache

# Install Extensions
#RUN apt-get update && apt-get install -y \
#       php7.0-mysql \
RUN docker-php-ext-install mysqli

# Enable apache2 rewrite
RUN a2enmod rewrite

# Copy custom Apache config
COPY config/apache2.conf /etc/apache2/apache2.conf
# COPY config/000-default.conf /etc/apache2/sites-available/000-default.conf
# COPY config/php.ini /usr/local/etc/php/

# Copy app config
COPY config/stockr.cfg /var/www/html/api/v1/config/stockr.cfg

# Copy files
COPY src/ /var/www/html/

# Restart apache
RUN service apache2 restart
