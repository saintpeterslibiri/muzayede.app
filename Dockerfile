FROM node:18-alpine AS backend

# Set timezone to Istanbul
RUN apk add --no-cache tzdata
ENV TZ=Europe/Istanbul

WORKDIR /app/backend

COPY backend/package*.json ./
RUN npm install

COPY backend/ .

EXPOSE 3000
CMD ["node", "server.js"]

FROM php:8.2-apache AS frontend

# Set timezone to Istanbul
RUN apt-get update && apt-get install -y tzdata && \
    ln -snf /usr/share/zoneinfo/Europe/Istanbul /etc/localtime && \
    echo Europe/Istanbul > /etc/timezone && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
ENV TZ=Europe/Istanbul

WORKDIR /var/www/html

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY frontend/ .

EXPOSE 80
