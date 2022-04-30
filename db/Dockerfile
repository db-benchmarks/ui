#FROM manticoresearch/manticore:4.2.0
FROM manticoresearch/manticore:dev
RUN apt -y update && apt -y install wget
#RUN wget https://repo.manticoresearch.com/manticore-repo.noarch.deb
RUN wget https://repo.manticoresearch.com/manticore-dev-repo.noarch.deb
#RUN dpkg -i manticore-repo.noarch.deb
RUN dpkg -i manticore-dev-repo.noarch.deb
RUN apt -y update
#RUN apt-get -y install manticore-columnar-lib=1.11.4-211223-327b3d4
RUN apt-get -y install manticore-columnar-lib
