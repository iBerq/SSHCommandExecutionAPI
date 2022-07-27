FROM ubuntu:latest
ENV USER client
ENV PASSWORD client
RUN apt-get update
RUN apt-get install -y netcat ssh iputils-ping
RUN mkdir /var/run/sshd
RUN chmod 0755 /var/run/sshd
RUN /usr/sbin/sshd
RUN useradd --create-home --shell /bin/bash --groups sudo $USER
RUN echo "$USER:$PASSWORD" | chpasswd
EXPOSE 22
ENTRYPOINT service ssh restart && bash