FROM ubuntu:focal
ENV DEBIAN_FRONTEND=noninteractive
#apt installs
RUN apt-get clean -y
RUN apt-get update --fix-missing -y
RUN apt-get upgrade -y
RUN apt-get install -y software-properties-common
RUN add-apt-repository ppa:ondrej/php
RUN apt-get update -y
RUN apt-get upgrade -y
RUN apt-get install -y php8.0
RUN apt-get install -y php8.0-mysql
RUN apt-get install -y php8.0-bcmath
RUN apt-get install -y php8.0-curl
RUN apt-get install -y php8.0-common
RUN apt-get install -y php8.0-mbstring
RUN apt-get install -y php8.0-dom
RUN apt-get install -y php8.0-zip
RUN apt-get install -y php8.0-imap
RUN apt-get install -y php8.0-imagick
RUN apt-get install -y php8.0-gd
RUN apt-get install -y php8.0-soap
RUN apt-get -y install libzip-dev zip php-zip
#install app on image
WORKDIR /app

#go!
WORKDIR /
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

WORKDIR /app/
ENTRYPOINT sh /app/dockerfiles/entrypoint.sh && /bin/bash