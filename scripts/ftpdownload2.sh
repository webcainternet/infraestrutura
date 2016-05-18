#!/bin/bash
rm -Rf /var/www/lojadietadukan.com.br/retornos_totalexpress/*
cd /var/www/lojadietadukan.com.br/retornos_totalexpress

pftp -i -n ftp.totalexpress.com.br <<END_SCRIPT
user dukan-wms ahS7u
cd retornos_dukan
mget *
END_SCRIPT
chown -R www-data. /var/www/lojadietadukan.com.br/retornos_totalexpress/
exit 0
