#!/bin/bash

# wait for app to be started (for db migrations)
echo "waiting app"
while [ "$(curl -so /dev/null -w '%{http_code}' http://localhost/login)" -ne 200 ];
do
	echo -n '.'
	sleep 1
done
echo ' ok'

# add tests data to db if not already here
php bin/console doctrine:fixtures:load --append


