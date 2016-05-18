#!/bin/bash
############################################
# Raid Checks for Zabbix
# Version 0.1
# Author: Auro Florentino <auro@merco.net>
# Created: Thu Oct 29 13:07:14 BRST 2009
# Last Updated: Tue Nov 17 15:48:10 BRST 2009
# Retorna 1 em caso de falha, 0 se OK.
############################################
case $1 in
	bsdperc)
	#zabbix ALL=(ALL) NOPASSWD: /usr/bin/grep
	if [ -f /var/log/dmesg.today ]; then
		# controladora PERC nas maquinas DELL com FreeBSD
		MSG=$(sudo grep -hi degraded /var/log/dmesg.today)
		RET=$?
		if [ "$RET" -ne "0" ]
		then
		        echo "0"
		else
		        echo "1"
		fi
	else
		echo "BSD Perc Raid Not Supported!"
	fi
	;;

	megaraid3)
	#zabbix ALL=(ALL) NOPASSWD: /usr/bin/grep
	if [ -f /var/log/dmesg ]; then
		# controladora MegaRaid nas HostVirtual01 e 02
		MSG=$(sudo grep -hi degraded /var/log/dmesg)
		RET=$?
		if [ "$RET" -ne "0" ]
		then
			echo "0"
		else
			echo "1"
		fi
	else
		echo "Megaraid 3 Raid Not Supported!"
	fi
	;;

	megaraid2)
	if [ -f /interdotnet/bin/megarc ]; then
		Erro=`megarc -ldInfo -a0 -Lall | grep Status: | grep -v OPTIMAL | wc -l`
		if [ $Erro -ne 0 ]; then
			echo "1"
			#echo "Raid do tipo $Raid com problemas "
		else
			echo "0"
			#echo "Raid do tipo $Raid Ok "
        	fi
	else
		echo "Megaraid Interdotnet Raid Not Supported!"
	fi
	;;

	megaraid)
	if [ -f /opt/MegaCli ]; then
		Erro=`/opt/MegaCli -LDInfo -LAll -a0 | grep -i State: | grep -iv OPTIMAL | wc -l`
		if [ $Erro -ne 0 ]; then
			echo "1"
		else
			echo "0"
		fi
	else
		echo "Megaraid Raid Not Supported!"
	fi
        ;;

	software)
	if [ -f /proc/mdstat ]; then
		RAIDC=$(grep -E "md[0-9]" /proc/mdstat | wc -l)
		RAIDI=$(grep -E "\[[U]{2,3}\]" /proc/mdstat | wc -l)
		if [ ${RAIDC} -ne ${RAIDI} ]; then
			echo "1"
		else
			echo "0"
		fi
	else
		echo "Software Raid Not Supported!"
	fi
	;;

	ipmi)
	if [ -f /usr/bin/ipmitool ]; then
		RAIDSTATUS=$(ipmitool -O open chassis status 2>&1 | grep -E "Drive\ Fault" | awk -F': ' '{print $2}')
		if [ "${RAIDSTATUS}" = "true" ]; then
			echo "1"
		else
			echo "0"
		fi
	else
		echo "IPMI Raid Not Supported!"
	fi
	;;

	mylex)
	if [ -f /proc/rd/status ]; then
		Erro=0
		cat /proc/rd/status |grep -i OK  2>&1> /dev/null || Erro=2
		if [ $Erro -eq 0 ]; then
			echo "0"
		else
			echo "1"
		fi
	else
		echo "Mylex Raid Not Suported!"
	fi
	;;

	*)
		echo "Utilize: $0 <bsdperc|megaraid|megaraid2|megaraid3|sofware|ipmi|mylex>"
	;;
esac
