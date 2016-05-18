#!/bin/bash
DIANOW=$(date +"%Y-%m-%d")
PASTAB=/srv/backup/mysql

#Movendo arquivos antigos
mv -f /srv/backup/mysql/*.2016* /srv/backup/mysql.2016/
echo show databases|mysql|egrep -v 'Database|information_schema'|awk '{print "mysqldump "$1" | gzip > /srv/backup/mysql/"$1".DIANOW.sql.gz;"}' | sed s/DIANOW/$DIANOW/g | bash -x
