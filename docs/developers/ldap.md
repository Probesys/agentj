# Working with LDAP

The development stack provides a LDAP server.
To use it, you must create a `example.com` domain in AgentJ.

- Active domain: ☑️ *(check the box)*
- Domain name: `example.com`
- SMTP server: `smtp.test`
- SMTP port: 25

Then, create a LDAP connector with the following values:

- host: `ldap`
- port: 1389
- bind DN: `cn=admin,dc=example,dc=com`
- password: `secret`
- BaseDN: `ou=users,dc=example,dc=com`
- Name field: `displayName`
- Email field: `mail`
- Alias field: `alias`
- Users filter: `(cn=*)`

Then, import the users.
You can login with one of the LDAP users:

- `alix@example.com` / `secret`
- `benedict@example.com` / `secret`
- `charlie@example.com` / `secret`
- `dominique@example.com` / `secret`
