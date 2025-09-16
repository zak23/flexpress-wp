FROM wordpress:6.4-php8.2-apache

# Install additional PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install additional tools
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI using composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer global require wp-cli/wp-cli composer/semver

# Create wp-cli wrapper script and fix permissions
RUN chmod +x /root/.composer/vendor/bin/wp \
    && echo '#!/bin/bash\ncd /var/www/html\n/root/.composer/vendor/bin/wp --allow-root "$@"' > /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy custom Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80
