#!/bin/bash
mkdir -p /root/.cert/encrypted.google.com/
save=0;
echo > /root/.cert/encrypted.google.com/encrypted.google.com.pem
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
		echo $i >> /root/.cert/encrypted.google.com/encrypted.google.com.pem;
	fi

	srch2="END CERTIFICATE"
	src2r=$(awk -v a="$i" -v b="$srch2" 'BEGIN{print index(a,b)}')
	if [ "${src2r}" != "0" ]
	then
                save=0;
	fi
done

wget https://certs.godaddy.com/repository/gd_bundle.crt -O /root/.cert/encrypted.google.com/gd.pem

c_rehash /root/.cert/encrypted.google.com/

IFS=$'\n';
for i in $(echo -ne "GET / HTTP/1.0\r\n\r\n" | openssl s_client -CApath ~/.cert/encrypted.google.com/ -connect encrypted.google.com:443); do
	srchr="Verify return code"
	srcrr=$(awk -v a="$i" -v b="$srchr" 'BEGIN{print index(a,b)}')
	if [ "${srcrr}" != "0" ]
	then
		echo $i
	fi
done
