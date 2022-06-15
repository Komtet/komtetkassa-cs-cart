FROM reneice/docker-centos-cscart

RUN yum install -y --enablerepo=remi,remi-php56 install php-soap ca-certificates