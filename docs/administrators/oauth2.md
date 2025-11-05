# Login with OAuth2

AgentJ allows your users to login with your SSO through the OAuth2 protocol.

> [!NOTE]
> The users must exist in the database first in order to be able to login.

## Information about the local login form

When OAuth2 is enabled, the default form is removed from the login page.
However, administrators may still need to login with this form.

**To access the local login form, add the `local=true` parameter to the login URL, e.g. `https://agentj.example.com/login?local=true`**

## Setup your OAuth server

Before enabling OAuth in AgentJ, you need to declare a new application on your OAuth server.

When doing it, if it asks you for a redirect URL, then fill it with: `https://your-agentj.example.com/connect/oauth/check` (obviously, adapt the domain to match with your own AgentJ server).

Note down any application client id and application secret that it gives you back (watch out: secrets are often **shown only once**).

## Configuration

Enable and setup OAuth2 login in the `.env` file.
As the configuration can change from a SSO provider to another, we document here some configuration that we tried.

### Authentik

```env
ENABLE_OAUTH=true
OAUTH_CLIENT_ID=<client_id>
OAUTH_CLIENT_SECRET=<client_secret>
OAUTH_URL_AUTHORIZE=https://authentik.example.com/application/o/authorize/
OAUTH_URL_ACCESS_TOKEN=https://authentik.example.com/application/o/token/
OAUTH_URL_RESOURCE_OWNER_DETAILS=https://authentik.example.com/application/o/userinfo/
OAUTH_SCOPES='email openid'
OAUTH_LOGIN_LABEL='Login with Authentik'
```

### Keycloak

```env
ENABLE_OAUTH=true
OAUTH_CLIENT_ID=<client_id>
OAUTH_CLIENT_SECRET=<client_secret>
OAUTH_URL_AUTHORIZE=https://keycloak.example.com/realms/<your_realm>/protocol/openid-connect/auth
OAUTH_URL_ACCESS_TOKEN=https://keycloak.example.com/realms/<your_realm>/protocol/openid-connect/token
OAUTH_URL_RESOURCE_OWNER_DETAILS=https://keycloak.example.com/realms/<your_realm>/protocol/openid-connect/userinfo
OAUTH_SCOPES='email openid'
OAUTH_LOGIN_LABEL='Login with Keycloak'
```

### Azure

```env
ENABLE_OAUTH=true
OAUTH_CLIENT_ID=<client_id>
OAUTH_CLIENT_SECRET=<client_secret>
OAUTH_URL_AUTHORIZE=https://login.microsoftonline.com/<issuer>/oauth2/v2.0/authorize
OAUTH_URL_ACCESS_TOKEN=https://login.microsoftonline.com/<issuer>/oauth2/v2.0/token
OAUTH_URL_RESOURCE_OWNER_DETAILS=https://graph.microsoft.com/oidc/userinfo
OAUTH_SCOPES='email openid'
OAUTH_LOGIN_LABEL='Login with Azure'
```

If you're not sure what is the `<issuer>` value in the URLs, note that you can find them in the [Microsoft Entra admin center](https://entra.microsoft.com) and navigate to: **Entra ID** > **App registrations** > **\<YOUR-APPLICATION>** > **Endpoints**.

> [!NOTE]
> Once the corresponding domain is created in AgentJ, you can edit it to add an Office365 connector.
> This allows to import email accounts and groups from your Microsoft account.
