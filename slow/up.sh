#!/usr/bin/env bash

docker run --rm -d -p 8080:80 --name my-apache-php-app -v "$PWD":/var/www/html php:7.2-apache
