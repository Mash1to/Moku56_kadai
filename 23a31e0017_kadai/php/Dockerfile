FROM php:8.2-apache


# 必要なPHP拡張をインストール
RUN docker-php-ext-install pdo pdo_mysql


# Apacheのmod_rewrite有効化（必要なら）
RUN a2enmod rewrite