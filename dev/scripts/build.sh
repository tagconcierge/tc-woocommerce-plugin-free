#!/usr/bin/env bash

docker-compose run -T --rm php-cli <<INPUT

composer install --dev
composer run check

rm vendor -Rf

composer install --no-dev

rm -rf dist/gtm-ecommerce-woo
mkdir -p dist/gtm-ecommerce-woo

cp -R assets src vendor gtm-ecommerce-woo.php readme.txt dist/gtm-ecommerce-woo/
cd dist && zip -r gtm-ecommerce-woo.zip ./gtm-ecommerce-woo

INPUT
