#! /bin/bash

# 读取配置
cflags=$(/usr/bin/mysql_config --cflags)
libs=$(/usr/bin/mysql_config --libs)
# 替换/为转义,sed要用
cflags=${cflags//\//\\\/}
libs=${libs//\//\\\/}

# 拼接sed命令参数
c="s/INCS =/INCS =${cflags}/g"
l="s/LIBS =/LIBS =${libs}/g"


export PATH=$PATH:/usr/local/bin:/sbin:/usr/sbin:/usr/bin:/bin:/usr/local/go/bin:$GOPATH/bin

cd  /src/viabtc/mintme_backend/marketprice
make

cd  /src/viabtc/mintme_backend/matchengine
make

cd  /src/viabtc/accesshttp/
make

cd /src/viabtc/accessws
make

cd /src/viabtc/alertcenter
make

cd /src/viabtc/readhistory
sed -i "$c" makefile
sed -i "$l" makefile
make
