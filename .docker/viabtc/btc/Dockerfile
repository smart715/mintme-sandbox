# set base image
FROM golang:1.12.10 AS btc-build

# install requirements
RUN apt-get update && apt-get install --no-install-recommends -y \
	software-properties-common \
	build-essential \ 
	libev-dev \
	libssl-dev \
	libjansson-dev \
	libmpdec-dev \
	libcurl4-gnutls-dev \
	default-libmysqlclient-dev \
	netcat \
	psmisc \
	&& rm -rf /var/lib/apt/lists/*

RUN mkdir -p /src
WORKDIR /src

# install kafka-client
RUN wget -qO - https://packages.confluent.io/deb/7.2/archive.key | apt-key add - && \
	add-apt-repository "deb [arch=amd64] https://packages.confluent.io/deb/7.2 stable main" && \
	add-apt-repository "deb https://packages.confluent.io/clients/deb buster main" && \
	apt-get update && apt-get install --no-install-recommends -y librdkafka-dev &&\
	rm -rf /var/lib/apt/lists/*

COPY build.sh /src/build.sh

# viabtc
# source directory contains abchosting viabtc version
ADD source /src/viabtc/

RUN sed -i 's/lmariadbclient/lmysqlclient/g' /src/viabtc/mintme_backend/matchengine/Makefile && \
	sed -i 's/lmariadbclient/lmysqlclient/g' /src/viabtc/mintme_backend/marketprice/Makefile && \
	sed -i 's/lmariadbclient/lmysqlclient/g' /src/viabtc/readhistory/makefile && \
	cd /src/viabtc/network &&\
	make clean &&\
	make &&\
	cd /src/viabtc/depends/hiredis &&\
	make &&\
	make install &&\
	ldconfig &&\
	cd /src/viabtc/utils/ &&\
	make &&\
	ldconfig &&\
	/bin/bash /src/build.sh &&\
	ldconfig

# Creating the /btc folder with .exe's
WORKDIR /btc/
CMD /bin/bash
COPY /btc/ /btc

RUN mv /src/viabtc/mintme_backend/matchengine/matchengine  /btc/matchengine/ &&\
	mv /src/viabtc/accesshttp/accesshttp.exe  /btc/accesshttp/ &&\
	mv /src/viabtc/mintme_backend/marketprice/marketprice  /btc/marketprice/ &&\
	mv /src/viabtc/accessws/accessws.exe  /btc/accessws/ &&\
	mv /src/viabtc/alertcenter/alertcenter.exe  /btc/alertcenter/ &&\
	mv /src/viabtc/readhistory/readhistory.exe  /btc/readhistory/ &&\
	chmod +x /btc/*.sh &&\
	chmod +x /btc/*/*.exe &&\
	chmod +x /btc/*/*.sh &&\
	chmod +x /tmp &&\
	mkdir -p /var/log/trade && \
	touch /var/log/trade/matchengine.log

COPY docker-entrypoint.sh /usr/local/bin
RUN chmod 755 /usr/local/bin/docker-entrypoint.sh

# marketprice (7416), matchengine (7316), matchengine cli (7317), readhistory (7424)
# alertcenter (4444), accesshttp (8080) and monitor (8081, 8091, 8364)
EXPOSE 7416 7316 7317 7424 4444 8080 8081 8091 8364

CMD /bin/bash

ENTRYPOINT ["docker-entrypoint.sh"]
