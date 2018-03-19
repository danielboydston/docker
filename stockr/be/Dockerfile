FROM php:7.2-cli

# Install php extensions
RUN apt-get update
RUN docker-php-ext-install mysqli

# Create working directory
RUN mkdir -p /app

# Copy src files
COPY src /app
WORKDIR /app

CMD ["php", "run.php"]

