FROM jenkins/jenkins:lts

USER root

## Install the docker agent
RUN apt-get update
RUN apt-get install -y \
     apt-transport-https \
     ca-certificates \
     curl \
     gnupg2 \
     software-properties-common
RUN apt-get -y install python-pip
RUN pip install awscli

RUN chown -R jenkins /var/jenkins_home/

## Install list of plugins form config/plugins.txt file
COPY config/plugins.txt /plugins.txt
RUN /usr/local/bin/install-plugins.sh < /plugins.txt
