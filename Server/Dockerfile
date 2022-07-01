FROM ubuntu:22.04
ENV USER server
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

#Create user for ssh connection
RUN mkdir /var/run/sshd
RUN chmod 0755 /var/run/sshd
RUN /usr/sbin/sshd
RUN useradd --create-home --shell /bin/bash --groups sudo $USER
RUN echo "$USER:123456" | chpasswd

#Setup the apache server
RUN service apache2 start

#Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

#Install git and zip for slim
RUN echo "y" | apt-get install git
RUN echo "y" | apt-get install zip

#Install slim
WORKDIR "/var/www/html"
RUN composer require slim/slim:"4.*"

#Install psr-7
RUN composer require nyholm/psr7 nyholm/psr7-server

#Install basepath
RUN composer require selective/basepath

#Delete file
RUN rm index.html

WORKDIR "/"

#Install mysql
RUN echo "y" | apt-get install mysql-server

#Setup mysql
RUN service mysql restart && echo "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password by 'root123@';" | mysql -u root -p
ADD mysql_secure_installation.sh /root/mysql_secure_installation.sh
RUN chmod +x /root/mysql_secure_installation.sh
RUN ./root/mysql_secure_installation.sh 'root123@' 'root123@'

#Add the files to server
ADD apache2.conf /etc/apache2/apache2.conf
ADD .htaccess /var/www/html/.htaccess
ADD index.php /var/www/html/index.php
ADD exec.php /var/www/html/exec.php
ADD machines.php /var/www/html/machines.php

#Enable mod_rewrite
RUN a2enmod rewrite

EXPOSE 22
ENTRYPOINT service ssh restart && service apache2 restart && service mysql restart && bash