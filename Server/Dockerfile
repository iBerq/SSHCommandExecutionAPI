FROM ubuntu:latest
ENV USER server
ENV PASSWORD 123456
RUN apt-get update
RUN echo 'root:root123@' | chpasswd

#Install ssh
RUN apt-get install -y netcat ssh iputils-ping

#Install php
RUN echo "php php/Areas select Europe" | debconf-set-selections
RUN echo "php php/Zones/Europe select Istanbul" | debconf-set-selections
RUN echo "y" | DEBIAN_FRONTEND="noninteractive" apt-get install php

#Install php-ssh2
RUN apt-get -y install php-ssh2
RUN echo "y" | apt-get install php-curl

#Create user for ssh connection
RUN mkdir /var/run/sshd
RUN chmod 0755 /var/run/sshd
RUN /usr/sbin/sshd
RUN useradd --create-home --shell /bin/bash --groups sudo $USER
RUN echo "$USER:$PASSWORD" | chpasswd

#Setup the apache server
RUN apache2ctl start
RUN echo "ServerName localhost" | tee /etc/apache2/conf-available/servername.conf
RUN a2enconf servername
RUN chown -R www-data:www-data /var/www/html

#Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

#Install git and zip for slim
RUN apt-get update
RUN echo "y" | apt-get install git
RUN echo "y" | apt-get install zip

#Install slim
WORKDIR "/var/www/html"
RUN composer require slim/slim:"4.*"

#Install psr-7
RUN composer require nyholm/psr7 nyholm/psr7-server

#Install basepath
RUN composer require selective/basepath

#Install php-mbstring
RUN echo "y" | apt-get install php-mbstring

#Install php-bcmath
RUN echo "y" | apt-get install php-bcmath

#Install amqplib
RUN composer require php-amqplib/php-amqplib:">=3.1"
RUN composer update

#Delete file
RUN rm index.html

WORKDIR "/"

#Install mysql
RUN echo "y" | apt-get install mysql-client
RUN echo "y" | apt-get install php-mysql

#Install rabbitmq-server
RUN echo "y" | apt-get install rabbitmq-server

#Install cron
RUN echo "y" | apt-get install cron

#Install nano
RUN apt-get install -y nano

#Add the files to server
ADD apache2.conf /etc/apache2/apache2.conf
ADD .htaccess /var/www/html/.htaccess
ADD index.php /var/www/html/index.php
ADD exec.php /var/www/html/exec.php
ADD database.php /var/www/html/database.php
ADD cron.php /var/www/html/cron.php
ADD queue_publisher.php /var/www/html/queue_publisher.php
ADD queue_consumer.php /var/www/html/queue_consumer.php

#Enable mod_rewrite
RUN a2enmod rewrite

EXPOSE 22
ENTRYPOINT service ssh restart && apache2ctl restart &&service cron restart && service rabbitmq-server restart && (php /var/www/html/queue_consumer.php >> /var/www/html/log.txt &) && bash