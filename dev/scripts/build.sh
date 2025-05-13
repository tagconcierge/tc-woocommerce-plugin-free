#!/bin/bash

RELEASE_VERSION=$(cat gtm-ecommerce-woo.php | grep 'Version:' | awk -F' ' '{print $3}')

docker compose -f event-inspector/docker-compose.yaml run -T --rm node-cli <<INPUT
yarn && yarn build
INPUT

docker compose run -T --rm php-cli <<INPUT
git config --global --add safe.directory /app

composer install
composer run check

rm vendor -Rf

composer install --no-dev --optimize-autoloader

rm -rf dist/*
mkdir -p dist/gtm-ecommerce-woo

cp -R assets src vendor gtm-ecommerce-woo.php readme.txt dist/gtm-ecommerce-woo/

#override link to dist gtm inspector
rm -f dist/gtm-ecommerce-woo/assets/gtm-event-inspector.js
cp event-inspector/dist/gtm-event-inspector.js dist/gtm-ecommerce-woo/assets/gtm-event-inspector.js

cd dist && zip -r gtm-ecommerce-woo.zip ./gtm-ecommerce-woo

cp gtm-ecommerce-woo.zip "gtm-ecommerce-woo-$RELEASE_VERSION.zip"

INPUT
