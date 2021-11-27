#!/bin/sh

cd /app
php ../composer.phar install

#start
echo "Tailing forever..."
tail -f dockerfiles/tailfile.txt


