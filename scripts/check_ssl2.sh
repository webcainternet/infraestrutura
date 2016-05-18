#!/bin/bash
mkdir -p /root/.cert/$1/
save=0;
echo > /root/.cert/$1/$1.pem
IFS=$'\n';
for i in $(echo -ne "GET / HTTP/1.0\r\n\r\n" | openssl s_client -showcerts -connect www.godaddy.com:443); do
	srch1="BEGIN CERTIFICATE"
	src1r=$(awk -v a="$i" -v b="$srch1" 'BEGIN{print index(a,b)}')
	if [ "${src1r}" != "0" ]
	then
		save=1;
        fi

	if [ $save -eq 1 ]
	then
		echo $i >> /root/.cert/$1/$1.pem;
	fi

	srch2="END CERTIFICATE"
	src2r=$(awk -v a="$i" -v b="$srch2" 'BEGIN{print index(a,b)}')
	if [ "${src2r}" != "0" ]
	then
                save=0;
	fi

        srchr="Verify return code"
        srcrr=$(awk -v a="$i" -v b="$srchr" 'BEGIN{print index(a,b)}')
        if [ "${srcrr}" != "0" ]
        then
                resultado1=$i
        fi
done

wget https://certs.godaddy.com/repository/gd_bundle.crt -O /root/.cert/$1/gd.pem

c_rehash /root/.cert/$1/

IFS=$'\n';
for i in $(echo -ne "GET / HTTP/1.0\r\n\r\n" | openssl s_client -CApath ~/.cert/$1/ -connect encrypted.google.com:443); do
	srchr="Verify return code"
	srcrr=$(awk -v a="$i" -v b="$srchr" 'BEGIN{print index(a,b)}')
	if [ "${srcrr}" != "0" ]
	then
		resultado2=$i
	fi
done
clear;
echo -e "RESULTADO:\n"
echo $resultado1
echo $resultado2
echo -e '\n'
