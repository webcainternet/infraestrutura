#!/bin/bash
# Get the dynamic IP (dirty, I know)
IP=`host -t a novatela.ddns.net | perl -nle '/((?:\d+\.?){4})/ && print $1' | head -n1`

# Update the hosts file
if test -n "$IP"; then
    grep -v backup01-sd /etc/hosts > /tmp/hosts
    echo "$IP backup01-sd" >> /tmp/hosts
    cp /tmp/hosts /etc/hosts
fi
