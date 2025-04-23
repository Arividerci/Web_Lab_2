FROM php:8.1-apache

# Установим нужные расширения PHP
RUN docker-php-ext-install pdo pdo_mysql

# Включаем mod_rewrite (для .htaccess)
RUN a2enmod rewrite

# Разрешаем .htaccess в apache2.conf
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Устанавливаем ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Меняем DocumentRoot на public/
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Копируем весь проект в контейнер
COPY . /var/www/html/

# Открываем порт
EXPOSE 80

# Запуск Apache в фореground
CMD ["apache2-foreground"]
