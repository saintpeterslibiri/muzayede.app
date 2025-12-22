FROM node:18-alpine AS backend

WORKDIR /app/backend

COPY backend/package*.json ./
RUN npm install

COPY backend/ .

EXPOSE 3000
CMD ["node", "server.js"]

FROM php:8.2-apache AS frontend

WORKDIR /var/www/html

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY frontend/ .

EXPOSE 80
