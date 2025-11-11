FROM php:8.2-cli

WORKDIR /app

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_sqlite

# Copiar archivos
COPY . .

# Exponer puerto
EXPOSE 8000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]