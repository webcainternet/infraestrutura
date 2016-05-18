#!/bin/bash
cd /tmp/
#cd /var/www/lojadietadukan.com.br/retornos_totalexpress

ftp -p ftp://dukan-wms:ahS7u@ftp.totalexpress.com.br <<END_SCRIPT
cd retornos_dukan
prompt
mget *
END_SCRIPT
exit 0
