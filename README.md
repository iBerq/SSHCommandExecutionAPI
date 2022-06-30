# SSHCommandExecutionApp
Jotform Internship Project

  **Developers:**
  - Emre Erdal
  - Ahmet Ayberk Yılmaz
  
# Do this to run the application
  - First build images for client and server from Dockerfiles
  
  **Then execute this commands to run the containers:**
  - docker network create -d bridge test
  - docker run -p 22 -p 80:80 --rm -ti --name server --network test {SERVER_IMAGE_NAME} bash 
  - docker run -p 22 --rm -ti --name client --network test {CLIENT_IMAGE_NAME} bash
