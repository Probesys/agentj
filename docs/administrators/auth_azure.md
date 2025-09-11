# OAuth with Azure

## OAuth Authentication

> [!IMPORTANT]
> **Requirements before enabling OAuth on Azure**  
> You need to declare your AgentJ as an application in your Azure account.  
> Use your AgentJ base URL, adding `/connect/azure/check` at the end, as redirect URI.
> Note down your application client id, application secret (watch out:  it is limited in time and **shown only once**).  
> You also need to create an Office365 connector in AgentJ and import users. See below

Once the 3 following `.env` values are **set with correct values**, login as AgentJ *SUPER_ADMIN* and create your Azure domain.

```
# Use your "Application client secret" indicated in your Azure account
OAUTH_AZURE_CLIENT_SECRET=abc12345-ab12-1234-ab12-abc432cb1234
# Use your "Application (client) ID" indicated in your Azure account
OAUTH_AZURE_CLIENT_ID=12345abc-4321-21ba-12ab-4321bc234abc
# Set to true to enable functionality
ENABLE_AZURE_OAUTH=true
```

## Office account importation

Once domain is created, you can edit it to **Add a connector** with parameters from your Azure account for email accounts and groups to be imported.
