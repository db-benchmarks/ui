FROM manticoresearch/manticore:5.0.2
RUN apt-get -y update && apt-get -y install wget
RUN wget https://repo.manticoresearch.com/manticore-repo.noarch.deb
RUN dpkg -i manticore-repo.noarch.deb
RUN apt-get -y update
RUN apt-get -y install manticore-columnar-lib
