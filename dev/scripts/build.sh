#!/bin/bash

RELEASE_VERSION=$(cat gtm-ecommerce-woo.php | grep 'Version:' | awk -F' ' '{print $3}')

docker-compose run -T --rm php-cli <<INPUT

composer install
composer run check

rm vendor -Rf

composer install --no-dev --optimize-autoloader

rm -rf dist/*
mkdir -p dist/gtm-ecommerce-woo

cp -R assets src vendor gtm-ecommerce-woo.php readme.txt dist/gtm-ecommerce-woo/

cd dist && zip -r gtm-ecommerce-woo.zip ./gtm-ecommerce-woo

cp gtm-ecommerce-woo.zip "gtm-ecommerce-woo-$RELEASE_VERSION.zip"

INPUT
