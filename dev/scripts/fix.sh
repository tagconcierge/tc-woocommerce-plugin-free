#!/bin/bash

docker-compose run -T --rm php-cli <<INPUT

composer install --dev
composer run fix
INPUT
