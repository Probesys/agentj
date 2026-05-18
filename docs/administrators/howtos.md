# HowTos

## How to get the SpamAssassin headers

If you want to known why an email has been flagged as spam, you can't use the web interface and you'll have to resort to shell. Before that, you'll have to get the ID of the email (first column in the web interface).

1. Connect to your AgentJ server and go to the folder where AgentJ is installed.
.
2. Run the following command: `docker compose exec db sh -c 'mariadb -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT * FROM quarantine WHERE mail_id=\"EMAILID\";" agentj'`. Modify the last `agentj` with your database name defined in the `.env` file and replace `EMAILID` by the ID of the email.
3. Identify the `X-Spam-Status` header in the email body. In this header you have the list of tests that contributed to the scoring.

## How to import from LDAP connector from CLI

Accounts from LDAP directories are imported every 12h. If you want to force an import, from an LDAP directory with a lot of entries, you should run this from CLI.

1. Connect to your AgentJ server and go to the folder where AgentJ is installed.
2. Get the id of the connector by running the following command: `docker compose exec db sh -c 'mariadb -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT id, name FROM connector;" agentj'`. Modify the last `agentj` with your database name defined in the `.env` file.
3. To launch the import, launch the folowing command: `docker compose exec -u www-data app /var/www/agentj/bin/console agentj:import-ldap $ID`. Replace `$ID` by the ID of the connector.

## How to bulk enable/disable reports/human auth

Reports and human authentication can be enabled and disabled for each email account in AgentJ. If you want to bulk enable/disable these for all accounts in a group, you can do this from CLI.

1. Connect to your AgentJ server and go to the folder where AgentJ is installed.
2. Get id of the group by running the following command: `docker compose exec db sh -c 'mariadb -uroot -p$MYSQL_ROOT_PASSWORD -e "SELECT id, name FROM groups;" agentj`. Modify the last `agentj` with your database name defined in the `.env` file.
3. To modify:
    - Reports: `docker compose exec db sh -c 'mariadb -uroot -p$MYSQL_ROOT_PASSWORD -e "UPDATE users SET report=1 WHERE id IN (SELECT user_id FROM user_groups WHERE groups_id=$ID);"'`. Use `report=1` to enable reports sending, `report=0` to disable reports sending. Replace `$ID` by the ID of the group.
    - Human authentication: `docker compose exec db sh -c 'mariadb -uroot -p$MYSQL_ROOT_PASSWORD -e "UPDATE users SET bypass_human_auth=1 WHERE id IN (SELECT user_id FROM user_groups WHERE groups_id=$ID);"'`. Use `bypass_human_auth=1` to disable human authentication, `bypass_human_auth=0` to enable human authentication. Replace `$ID` by the ID of the group.
