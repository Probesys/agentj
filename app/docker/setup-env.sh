#!/bin/sh

set -e

env_file=/var/www/agentj/.env
cp /var/www/agentj/.env.example $env_file
sed -i "s|\$AGENTJ_VERSION|$VERSION|g" $env_file
sed -i "s|\$SF_APP_ENV|$SF_APP_ENV|g" $env_file
sed -i "s|\$SF_APP_SECRET|$SF_APP_SECRET|g" $env_file
sed -i "s|\$SF_TOKEN_ENCRYPTION_IV|$SF_TOKEN_ENCRYPTION_IV|g" $env_file
sed -i "s|\$SF_TOKEN_ENCRYPTION_SALT|$SF_TOKEN_ENCRYPTION_SALT|g" $env_file
sed -i "s|\$SF_SENTRY_DSN|$SF_SENTRY_DSN|g" $env_file
sed -i "s|\$DB_NAME|$DB_NAME|g" $env_file
sed -i "s|\$DB_USER|$DB_USER|g" $env_file
sed -i "s|\$DB_PASSWORD|$DB_PASSWORD|g" $env_file
sed -i "s|\$DB_HOST|$DB_HOST|g" $env_file
sed -i "s|\$APP_URL|$APP_URL|g" $env_file
sed -i "s|\$SMTP_FROM|$SMTP_FROM|g" $env_file
sed -i "s|\$ENABLE_AZURE_OAUTH|$ENABLE_AZURE_OAUTH|g" $env_file
sed -i "s|\$OAUTH_AZURE_CLIENT_ID|$OAUTH_AZURE_CLIENT_ID|g" $env_file
sed -i "s|\$OAUTH_AZURE_CLIENT_SECRET|$OAUTH_AZURE_CLIENT_SECRET|g" $env_file
sed -i "s|\$ENABLE_OAUTH|$ENABLE_OAUTH|g" $env_file
sed -i "s|\$OAUTH_CLIENT_ID|$OAUTH_CLIENT_ID|g" $env_file
sed -i "s|\$OAUTH_CLIENT_SECRET|$OAUTH_CLIENT_SECRET|g" $env_file
sed -i "s|\$OAUTH_URL_AUTHORIZE|$OAUTH_URL_AUTHORIZE|g" $env_file
sed -i "s|\$OAUTH_URL_ACCESS_TOKEN|$OAUTH_URL_ACCESS_TOKEN|g" $env_file
sed -i "s|\$OAUTH_URL_RESOURCE_OWNER_DETAILS|$OAUTH_URL_RESOURCE_OWNER_DETAILS|g" $env_file
sed -i "s|\$OAUTH_SCOPES|$OAUTH_SCOPES|g" $env_file
sed -i "s|\$OAUTH_LOGIN_LABEL|$OAUTH_LOGIN_LABEL|g" $env_file
sed -i "s|\$TRUSTED_PROXIES|$TRUSTED_PROXIES|g" $env_file
sed -i "s|\$TZ|$TZ|g" $env_file
sed -i "s|\$DEFAULT_LOCALE|$DEFAULT_LOCALE|g" $env_file
sed -i "s|\$HISTORY_RETENTION_DAYS|${HISTORY_RETENTION_DAYS:-30}|g" $env_file
sed -i "s|\$FEATURE_FLAG_HINT_INDEX|${FEATURE_FLAG_HINT_INDEX:-false}|g" $env_file
