#!/bin/bash

# USE ONLY WHEN UPGRADING FROM <2 to >=2
#
# This script is intended to be run on a running AgentJ instance, BEFORE any upgrade actions
# It prints
# - Symfony encryption tokens, to be put in .env (BEFORE upgrade)
# - DKIM public/private keys, to be inserted in the updated database (AFTER upgrade)

dx="docker compose exec app"

# app tokens

printf "
# add this to .env

"
$dx grep -E 'TOKEN_ENCRYPTION_SALT|TOKEN_ENCRYPTION_IV|APP_SECRET' /var/www/agentj/.env | sed 's/^/SF_/'

# DKIM

printf "
# once database is started and migrated, review and execute this
"
for domain in $($dx ls -1 /var/db/dkim/keys/);
do
    for keyfile in $($dx find /var/db/dkim/keys/"$domain"/ -name "*.private");
	do
		_selector="${keyfile##*/}"
		selector="${_selector%.private}"
		pubkey=$($dx cat "/var/db/dkim/keys/$domain/$selector.txt" | tr -d '\n' | grep -oP '(?<=p\=).*(?=\))' | sed -e 's/[ "\t]//g')
		privkey=$($dx grep -v '^-----' "$keyfile" | tr -d '\n')
		printf "
insert into dkim (domain_name, selector, private_key, public_key) values ('%s',\
'%s', '-----BEGIN RSA PRIVATE KEY-----\\\\n%s\\\\n-----END RSA PRIVATE KEY-----',\
'-----BEGIN PUBLIC KEY-----\\\\n%s\\\\n-----END PUBLIC KEY-----');\n" "$domain" "$selector" "$privkey" "$pubkey"
	    printf "update domain set domain_keys_id = (select id from dkim where domain_name = '%s') where domain = '%s';\n" "$domain" "$domain"
    done
done
