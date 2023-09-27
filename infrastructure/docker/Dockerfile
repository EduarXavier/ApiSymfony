# Based on https://github.com/JangChun/docker-lemp

FROM ubuntu:22.04

MAINTAINER Hely Suarez Marin <helysm@gopenux.com>

WORKDIR /var/www


################ Install packages ################

RUN apt update && apt -y upgrade && apt install lsb-release ca-certificates apt-transport-https software-properties-common -y

RUN mkdir -p /run/php/

RUN add-apt-repository ppa:ondrej/php

RUN apt update && \
        DEBIAN_FRONTEND=noninteractive apt -qq install -y \
        php8.1 php8.1-mongodb php8.1-fpm php8.1-mbstring php8.1-cgi nano \
        php8.1-curl php8.1-dev php8.1-gd php8.1-imap php8.1-intl php8.1-zmq \
        php8.1-pspell php8.1-sqlite3 php8.1-tidy php8.1-zip php8.1-xdebug \
        php8.1-xmlrpc php8.1-xsl libssl-dev php8.1-dev php8.1-apcu htop php-imagick pkg-config \
        nginx curl supervisor unzip nmap sudo apt-utils vim acl inetutils-ping nano rsync && \
        rm -rf /var/lib/apt/lists/*

RUN update-alternatives --set php /usr/bin/php8.1

## Configuration
RUN sed -i 's/^listen\s*=.*$/listen = 127.0.0.1:9000/' /etc/php/8.1/fpm/pool.d/www.conf && \
    cd /etc/php/8.1/cli/conf.d && \
    ln -sf /etc/php/8.1/mods-available/mongodb.ini 20-mongodb.ini

RUN apt-get update && apt-get install -y php8.1-amqp

COPY ./infrastructure/docker/files/root /

################ Install packages ################


################ Section SSH ################

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y openssh-server && \
    mkdir /var/run/sshd && \
    echo 'root:root' | chpasswd && \
    useradd -s /bin/bash docker && echo "docker:docker" | chpasswd && \
    mkdir -p /home/docker/.ssh && chown -R docker:docker /home/docker && \
    echo 'docker ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers.d/docker && \
    sed -i 's/PermitRootLogin without-password/PermitRootLogin yes/' /etc/ssh/sshd_config && \
    sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd

ENV NOTVISIBLE "in users profile"

RUN echo "export VISIBLE=now" >> /etc/profile

################ Section SSH ################


################ Install composer ################

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

################ Install composer ################

################ Disable Xdebug by default so we improve performance ################
#RUN sudo phpdismod xdebug && service php8.1-fpm restart
################ Disable Xdebug by default so we improve performance ################

################ Custom Config ################

RUN usermod -a -G docker www-data && usermod -a -G www-data docker
RUN mkdir -p /var/www/var && mkdir -p /var/www/vendor
COPY --chown=docker:docker ./code /var/www
RUN chown -R docker:docker /var/www

USER root
COPY ./infrastructure/authorized_keys /home/docker/.ssh
RUN chown -R docker:docker /home/docker
################ Custom Config ################


ENV TERM xterm
ENV ON_ENTRY_SCRIPT=$ON_ENTRY_SCRIPT

RUN echo "export PATH=$PATH" > /etc/environment

VOLUME  ["/var/www"]
#VOLUME  ["/root/.ssh/"]

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
