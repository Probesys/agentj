# Changelog of AgentJ

## 2026-02-27 - 2.4.5

### Bug fixes

- Fix search of messages ([d7319776](https://github.com/Probesys/agentj/commit/d7319776), [613b7532](https://github.com/Probesys/agentj/commit/613b7532), [57bc0c7a](https://github.com/Probesys/agentj/commit/57bc0c7a))

## 2026-01-29 - 2.4.4

### Bug fixes

- Fix redirecting to the "Referer" URL ([ec585b3a](https://github.com/Probesys/agentj/commit/ec585b3a))

## 2026-01-16 - 2.4.3

### Bug fixes

- Fix sorting messages by recipient address ([e03a9501](https://github.com/Probesys/agentj/commit/e03a9501))
- Don't mark initial user as an alias of himself when importing from M365 ([ed4cff1e](https://github.com/Probesys/agentj/commit/ed4cff1e))
- Fix checking proxy address when importing alias from M365 ([927adcc7](https://github.com/Probesys/agentj/commit/927adcc7))

### Maintenance

- Refactor creation of aliases ([f24fb50c](https://github.com/Probesys/agentj/commit/f24fb50c))

## 2025-12-19 - 2.4.2

### Bug fixes

- Execute the `messenger:consume` command as `www-data` ([0ad0113e](https://github.com/Probesys/agentj/commit/0ad0113e))
- Fix setting the default locale ([b428b41d](https://github.com/Probesys/agentj/commit/b428b41d))
- Fix autorelease with a high number of untreated messages ([faccbb2c](https://github.com/Probesys/agentj/commit/faccbb2c))
- Fix an English translation in the dashboard stats ([e9b39cdf](https://github.com/Probesys/agentj/commit/e9b39cdf))

## 2025-12-18 - 2.4.1

### Maintenance

- Schedule the auto release of emails for users without human authentication ([63bc4ec0](https://github.com/Probesys/agentj/commit/63bc4ec0))
- No longer release messages when sending authentication mail ([d0b72d7c](https://github.com/Probesys/agentj/commit/d0b72d7c))
- Fix the CI failing randomly ([047c4867](https://github.com/Probesys/agentj/commit/047c4867))

## 2025-12-17 - 2.4.0

### Migration notes

#### Persistent volumes

Persistent volumes are added for the `app` container to preserve data across restarts.

**Before upgrading**, backup the following directories from the running container:

- Backup var/log/ directory: `docker compose cp app:/var/www/agentj/var/log ./backup-log`
- Backup public/files/ directory: `docker compose cp app:/var/www/agentj/public/files ./backup-files` (this command may fail if the directory doesn't exist, that's normal)

**After the upgrade**, the new volumes will be created automatically. Restore the backed up data:

- `docker compose cp ./backup-log app:/var/www/agentj/var/log`
- `docker compose cp ./backup-files app:/var/www/agentj/public/files`

#### History retention

You can now configure the number of days of history retention.
This impacts the history of incoming/outgoing emails and logs as well.
To change the number of days of retention, set the `HISTORY_RETENTION_DAYS` environment variable in your `.env` file.
It defaults to 30 days, as before, so it is not required to set this value if you want to keep the existing behaviour.

### Features

- Improve the customisation options of the human authentication workflow
    - Allow to customise the human authentication page stylesheet ([1818c951](https://github.com/Probesys/agentj/commit/1818c951))
    - Use the custom domain logo on the human authentication page ([3ff4acf9](https://github.com/Probesys/agentj/commit/3ff4acf9))
    - Redesign the human authentication page ([3fa49177](https://github.com/Probesys/agentj/commit/3fa49177))
    - Allow to customise the human authentication footer ([0b954598](https://github.com/Probesys/agentj/commit/0b954598))
- Allow to always display the content of mails in quarantine ([8b2f6f1b](https://github.com/Probesys/agentj/commit/8b2f6f1b))
- Allow to change the number of days of history retention ([6fc56557](https://github.com/Probesys/agentj/commit/6fc56557))
- Allow to select LDAP "encryption" and "version" ([54677058](https://github.com/Probesys/agentj/commit/54677058))
- Change the domain "messages" in a "customisation" section ([fe6d0cbe](https://github.com/Probesys/agentj/commit/fe6d0cbe))
- Allow to customise the list of messages in the report ([4fbe79a4](https://github.com/Probesys/agentj/commit/4fbe79a4))

### Improvements

- Extend the report token lifetime to 7 days ([a292d9c4](https://github.com/Probesys/agentj/commit/a292d9c4))
- Improve the performance of the dashboard ([27182a9b](https://github.com/Probesys/agentj/commit/27182a9b))
- Improve the performance of the navigation ([a4ba1284](https://github.com/Probesys/agentj/commit/a4ba1284))
- Improve the performance of the list of messages ([6382259e](https://github.com/Probesys/agentj/commit/6382259e), [3892e0d2](https://github.com/Probesys/agentj/commit/3892e0d2), [a02a6392](https://github.com/Probesys/agentj/commit/a02a6392))
- Improve the performance of the advanced search ([07f973a9](https://github.com/Probesys/agentj/commit/07f973a9), [19ad042e](https://github.com/Probesys/agentj/commit/19ad042e))
- Hide the local login form when OAuth2 is enabled ([2ebba4b5](https://github.com/Probesys/agentj/commit/2ebba4b5))
- Rename the "captcha" term into "human authentication" in the interface ([188706f8](https://github.com/Probesys/agentj/commit/188706f8))
- Add a `URL_HUMAN_AUTHENTICATION` variable similar to `URL_CAPTCHA` ([d5eb5cc1](https://github.com/Probesys/agentj/commit/d5eb5cc1))

### Bug fixes

- Fix the creation of alerts ([209cd3b6](https://github.com/Probesys/agentj/commit/209cd3b6))
- Fix the "back to user" button not showing original user after session expiration ([4ad3836f](https://github.com/Probesys/agentj/commit/4ad3836f))
- Associate LDAP aliases to their connector ([32d0efa9](https://github.com/Probesys/agentj/commit/32d0efa9))
- Append file extension to the uploaded filenames ([ea53f84e](https://github.com/Probesys/agentj/commit/ea53f84e))
- Use the user locale to translate the emails ([b692ba44](https://github.com/Probesys/agentj/commit/b692ba44))
- Fix the sender email input in the domain form ([ac56cf60](https://github.com/Probesys/agentj/commit/ac56cf60))

### Administration

- Add persistent Docker volumes for app data ([19f9e749](https://github.com/Probesys/agentj/commit/19f9e749))
- Schedule synchronization of the connectors each 12 hours ([e7400add](https://github.com/Probesys/agentj/commit/e7400add))
- Run Symfony Messenger worker with a Docker service ([cd485617](https://github.com/Probesys/agentj/commit/cd485617))
- Send emails asynchronously with Messenger ([294476c8](https://github.com/Probesys/agentj/commit/294476c8))
- Truncate outgoing messages in truncate-message-since-days command ([e8e28b10](https://github.com/Probesys/agentj/commit/e8e28b10))
- Add a command to "auto release" messages ([f49c0541](https://github.com/Probesys/agentj/commit/f49c0541))

### Documentation

- Update the roadmap for 2026 ([4f612745](https://github.com/Probesys/agentj/commit/4f612745))

### Maintenance

- Install the Symfony Scheduler component ([09e7dcc2](https://github.com/Probesys/agentj/commit/09e7dcc2))
- Create a BaseRepository for all the repositories ([c1a7bdda](https://github.com/Probesys/agentj/commit/c1a7bdda))
- Provide a new frontend stack base ([56730428](https://github.com/Probesys/agentj/commit/56730428))
- Display custom error page only in production ([d8ed3097](https://github.com/Probesys/agentj/commit/d8ed3097))
- Add a lock on the synchronize connectors handler ([2999aef0](https://github.com/Probesys/agentj/commit/2999aef0))
- Don't try to assign groups if target groups are empty ([e264830d](https://github.com/Probesys/agentj/commit/e264830d))
- Refactor decrypting the report token ([ddfd1a90](https://github.com/Probesys/agentj/commit/ddfd1a90))
- Fix the markup of the header ([bcd82d58](https://github.com/Probesys/agentj/commit/bcd82d58))
- Update the default email templates ([a14d8510](https://github.com/Probesys/agentj/commit/a14d8510))
- Extract a `HumanAuthentications` controller ([af91f841](https://github.com/Probesys/agentj/commit/af91f841))

## 2025-12-15 - 2.3.4

### Bug fixes

- Add DKIM signature to all outgoing emails ([4fac5137](https://github.com/Probesys/agentj/commit/4fac5137))
- Fix saving status and captcha time on human authorization ([2be8f836](https://github.com/Probesys/agentj/commit/2be8f836))

## 2025-11-19 - 2.3.3

### Maintenance

- Upgrade to Symfony 7.3 ([bd2cc6df](https://github.com/Probesys/agentj/commit/bd2cc6df), [cf7e1bcf](https://github.com/Probesys/agentj/commit/cf7e1bcf))
- Update the dependencies ([aa511934](https://github.com/Probesys/agentj/commit/aa511934))

## 2025-11-06 - 2.3.2

### Bug fixes

- Fix releasing untreated messages ([ee0aaa64](https://github.com/Probesys/agentj/commit/ee0aaa64))
- Fix creation of users failing because of uninitialized aliases collection ([3dca0332](https://github.com/Probesys/agentj/commit/3dca0332))

## 2025-10-10 - 2.3.1

This release includes changes from 2.2.4.

### Bug fixes

- Don't try to release messages that have already been delivered ([db80c719](https://github.com/Probesys/agentj/commit/db80c719))
- Dont send auth request if sender is in whitelist or blacklist ([3d9557ce](https://github.com/Probesys/agentj/commit/3d9557ce))
- Dont send auth request if mail is too old ([71f4b923](https://github.com/Probesys/agentj/commit/71f4b923))

### Maintenance

- Restart Docker containers unless stopped ([8470d50d](https://github.com/Probesys/agentj/commit/8470d50d))

## 2025-09-26 - 2.3.0

### Migration notes

- You can remove the `APP_DOMAIN` variable from the root `.env` file and you must add a `APP_URL` variable (ex. `https://agentj.example.com`).
- You must add a `SMTP_FROM` variable in the root `.env` file as it's no longer computed from the domain. Recommended value is `no-reply@<domain>`.
- You can add a `AMAVIS_PROCESSES` in the root `.env` file to modify the number of Amavis processes (default: 3)
- The previous `OAUTH_AZURE` system is now deprecated; a new generic OAuth2 system is available and should be prefered ([see documentation](/docs/administrators/oauth2.md))

### Features

- Provide generic OAuth2 login ([a75cad97](https://github.com/Probesys/agentj/commit/a75cad97))
- Improvements to the LDAP connector overall:
    - Reorganize the LDAP form ([9825248a](https://github.com/Probesys/agentj/commit/9825248a))
    - Allow to attach imported users to groups ([7c4df84a](https://github.com/Probesys/agentj/commit/7c4df84a))
    - Allow to import shared mailboxes from LDAP ([e57bf934](https://github.com/Probesys/agentj/commit/e57bf934))
    - Don't import aliases if they match the main email ([828f1244](https://github.com/Probesys/agentj/commit/828f1244))
    - Handle unmanaged domains on import ([2a1f8b9d](https://github.com/Probesys/agentj/commit/2a1f8b9d))
    - Ignore invalid emails when importing LDAP users ([db5225da](https://github.com/Probesys/agentj/commit/db5225da))
    - Remove the unused connector login field ([5f8e9e2d](https://github.com/Probesys/agentj/commit/5f8e9e2d))
    - Use the users' origin connectors for LDAP authentication ([54949d53](https://github.com/Probesys/agentj/commit/54949d53))
    - Fix typos in the LDAP connector form ([9a189954](https://github.com/Probesys/agentj/commit/9a189954))
- Add `Auto-Submitted` and `X-Auto-Response-Suppress` headers to the emails generated and sent by AgentJ ([ee109c5f](https://github.com/Probesys/agentj/commit/ee109c5f))

### Maintenance

- Add a `SMTP_FROM` env variable ([89cab663](https://github.com/Probesys/agentj/commit/89cab663))
- Add ability to change the number of Amavis processes ([df20e98b](https://github.com/Probesys/agentj/commit/df20e98b))
- Add more details to the IMAP connection errors' logs ([6e7adf48](https://github.com/Probesys/agentj/commit/6e7adf48))
- Paginate resource-intensive pages on the backend:
    - Advanced-search ([ef7c1627](https://github.com/Probesys/agentj/commit/ef7c1627))
    - List of users ([16793045](https://github.com/Probesys/agentj/commit/16793045), [b92cc6e6](https://github.com/Probesys/agentj/commit/b92cc6e6), [d10faadc](https://github.com/Probesys/agentj/commit/d10faadc))
    - Domains ([4dddd393](https://github.com/Probesys/agentj/commit/4dddd393))
- Upgrade to PHPImap 6.2 ([7c8b2cd2](https://github.com/Probesys/agentj/commit/7c8b2cd2))
- Upgrade OpenDKIM container to Trixie ([9a8aae25](https://github.com/Probesys/agentj/commit/9a8aae25))
- Fix wording of the Office365 connector ([2404c189](https://github.com/Probesys/agentj/commit/2404c189))

### Documentation

- Reorganize the documentation ([36f37a92](https://github.com/Probesys/agentj/commit/36f37a92))
- Document the versioning policy ([5ca3ae0b](https://github.com/Probesys/agentj/commit/5ca3ae0b))
- Add missing instructions to load fixtures data ([26b4f529](https://github.com/Probesys/agentj/commit/26b4f529))
- Add instructions to debug with Xdebug and VS Code ([891a1d43](https://github.com/Probesys/agentj/commit/891a1d43), [f912a3f0](https://github.com/Probesys/agentj/commit/f912a3f0))

### Developers

- Add new "make" commands to manage the database ([00094682](https://github.com/Probesys/agentj/commit/00094682))
- Provide an `.editorconfig` file ([d0f8c3c8](https://github.com/Probesys/agentj/commit/d0f8c3c8))
- Provide a LDAP server in development mode ([2fe36212](https://github.com/Probesys/agentj/commit/2fe36212), [9468ff12](https://github.com/Probesys/agentj/commit/9468ff12))
- Build absolute URLs with urlGenerator ([5d3f536f](https://github.com/Probesys/agentj/commit/5d3f536f))
- Refactor getting users' emails ([6adbcd33](https://github.com/Probesys/agentj/commit/6adbcd33))
- Decrease tests timeout to 15 seconds ([d5da23c8](https://github.com/Probesys/agentj/commit/d5da23c8))
- Replace calls to apt by apt-get ([12d9001f](https://github.com/Probesys/agentj/commit/12d9001f))
- Don't set the lock setting as it broke generation of migrations ([f80393a3](https://github.com/Probesys/agentj/commit/f80393a3))
- Add the `app/public/files` folder to gitignore ([bc1014a4](https://github.com/Probesys/agentj/commit/bc1014a4))
- Remove unused code, templates and files ([48ad54de](https://github.com/Probesys/agentj/commit/48ad54de))

## 2025-11-06 - 2.2.5

### Bug fixes

- Fix releasing untreated messages ([f3a5c471](https://github.com/Probesys/agentj/commit/f3a5c471))
- Fix creation of users failing because of uninitialized aliases collection ([f5e9233a](https://github.com/Probesys/agentj/commit/f5e9233a))

## 2025-10-10 - 2.2.4

### Bug fixes

- Don't try to release messages that have already been delivered ([db80c719](https://github.com/Probesys/agentj/commit/db80c719))
- Dont send auth request if sender is in whitelist or blacklist ([3d9557ce](https://github.com/Probesys/agentj/commit/3d9557ce))
- Dont send auth request if mail is too old ([71f4b923](https://github.com/Probesys/agentj/commit/71f4b923))

### Maintenance

- Restart Docker containers unless stopped ([8470d50d](https://github.com/Probesys/agentj/commit/8470d50d))

## 2025-09-26 - 2.2.3

### Bug fixes

- Order messages by date desc by default ([44e67fb9](https://github.com/Probesys/agentj/commit/44e67fb9))
- Fix user form when editing admins ([14888352](https://github.com/Probesys/agentj/commit/14888352))

### Maintenance

- Upgrade OpenDKIM container to Trixie ([cc3d155c](https://github.com/Probesys/agentj/commit/cc3d155c))

## 2025-09-09 - 2.2.2

### Bug fixes

- Fix sending auth email if fullname is null ([98ec316](https://github.com/Probesys/agentj/commit/98ec316))
- Replace `NB_UNTREATED_MESSAGES` in the reports ([f1ed1e1](https://github.com/Probesys/agentj/commit/f1ed1e1))
- Sync user domain with his email domain on edition ([430ea71](https://github.com/Probesys/agentj/commit/430ea71))
- Use the domain sender when sending reports ([79d53c0](https://github.com/Probesys/agentj/commit/79d53c0))
- Remove default mailer From and Sender headers ([3f17c39](https://github.com/Probesys/agentj/commit/3f17c39))
- Fix migration 20250424140106 to handle msgrcpts without msgs ([5949ec1](https://github.com/Probesys/agentj/commit/5949ec1))

## 2025-08-25 - 2.2.1

### Bug fixes

- Don't prefetch links with Turbo ([4e9f607](https://github.com/Probesys/agentj/commit/4e9f607))

## 2025-07-31 - 2.2.0

### Security

- Make report tokens unique per email ([1fb34ae](https://github.com/Probesys/agentj/commit/1fb34ae))
- Do not allow users to reauthorize themselves ([34449c4](https://github.com/Probesys/agentj/commit/34449c4))

### Features

- Send only one validation email for multiple receivers of the same domain ([31840a6](https://github.com/Probesys/agentj/commit/31840a6))

### Bug fixes

- Show the preview button only for untreated emails ([89b155b](https://github.com/Probesys/agentj/commit/89b155b))
- Close the modal after submitting a user form ([043b3f2](https://github.com/Probesys/agentj/commit/043b3f2))
- Fetch the sender email correctly for virus alerts ([27d2b32](https://github.com/Probesys/agentj/commit/27d2b32))
- Unregister residual service workers ([5017429](https://github.com/Probesys/agentj/commit/5017429))

### Documentation

- Indicate to checkout to the desired version in the installation guide ([2ec3fbd](https://github.com/Probesys/agentj/commit/2ec3fbd))

### Maintenance

- Upgrade to MariaDB 11.4 ([5a97b3b](https://github.com/Probesys/agentj/commit/5a97b3b))
- Enable mariadbupgrade in healthcheck ([a57f8d4](https://github.com/Probesys/agentj/commit/a57f8d4))
- Decrease the Amavis `dsn_cutoff` level ([c8219f8](https://github.com/Probesys/agentj/commit/c8219f8))
- Rework the Postfix configurations ([5416231](https://github.com/Probesys/agentj/commit/5416231))
- Setup PHPStan up to level 7 ([f7bdcd5](https://github.com/Probesys/agentj/commit/f7bdcd5))
- Setup PHP\_CodeSniffer ([f7e926c](https://github.com/Probesys/agentj/commit/f7e926c), [9aa8625](https://github.com/Probesys/agentj/commit/9aa8625))
- Update the dependencies ([85ee60b](https://github.com/Probesys/agentj/commit/85ee60b), [e2d2569](https://github.com/Probesys/agentj/commit/e2d2569), [21c9638](https://github.com/Probesys/agentj/commit/21c9638))
- Refactor the research of messages ([cc39352](https://github.com/Probesys/agentj/commit/cc39352))
- Refactor the native SQL queries ([cac9805](https://github.com/Probesys/agentj/commit/cac9805))

## 2025-07-31 - 2.1.6

This is a security release, it fixes a SQL injection only exploitable by a malicious administrator.
See the commit for more information.
Updating AgentJ is recommended.

### Security

- Fix a SQL injection in advanced search ([0b14160](https://github.com/Probesys/agentj/commit/0b14160))

## 2025-07-29 - 2.1.5

### Technical

- Don't use CDNs to load assets ([f6d3a5d](https://github.com/Probesys/agentj/commit/f6d3a5d))

## 2025-07-17 - 2.1.4

### Bug fixes

- Import whitelisted domains with their "@" ([9b71c68](https://github.com/Probesys/agentj/commit/9b71c68))
- Fix searching and listing wblists ([c253330](https://github.com/Probesys/agentj/commit/c253330))
- Don't allow to delete generic domain wblists ([66428c4](https://github.com/Probesys/agentj/commit/66428c4))

## 2025-07-04 - 2.1.3

### New

- Allow to import authorized domains ([40a41ed](https://github.com/Probesys/agentj/commit/40a41ed))

### Bug fixes

- Fix editing LDAP connector failure ([d50d38f](https://github.com/Probesys/agentj/commit/d50d38f))
- Fix the type of Wblist on file importation ([7f1e049](https://github.com/Probesys/agentj/commit/7f1e049))
- Fix file inputs not displaying value ([5e3f543](https://github.com/Probesys/agentj/commit/5e3f543))
- Translate "import" button in English ([9fbc040](https://github.com/Probesys/agentj/commit/9fbc040))

### Documentation

- Update the main documentation file ([1358579](https://github.com/Probesys/agentj/commit/1358579))

### Developers

- Provide a Makefile with useful Docker shortcuts ([796ff97](https://github.com/Probesys/agentj/commit/796ff97))
- Provide scripts to work in containers more easily ([64cec74](https://github.com/Probesys/agentj/commit/64cec74))
- Provide a command to run the linters ([3baf39e](https://github.com/Probesys/agentj/commit/3baf39e))
- Provide a command to release a version ([8ef4fa1](https://github.com/Probesys/agentj/commit/8ef4fa1))

## 2025-05-23 - 2.1.2

### Bug fixes

- Allow admins to search on recipients ([5aeaae4](https://github.com/Probesys/agentj/commit/5aeaae4))
- Show delivered "spammy" emails in the correct list ([93d6a25](https://github.com/Probesys/agentj/commit/93d6a25))
- Fix importing white lists ([5c091c3](https://github.com/Probesys/agentj/commit/5c091c3))
- Fix wblist import allowing bad values ([d4f610d](https://github.com/Probesys/agentj/commit/d4f610d))
- Fix translations of alerts ([9b4ae6c](https://github.com/Probesys/agentj/commit/9b4ae6c))
- Don't slice alert subject in tables ([95a4d20](https://github.com/Probesys/agentj/commit/95a4d20))
- Fix various issues related to the IMAP connector form ([eca19a4](https://github.com/Probesys/agentj/commit/eca19a4), [ab3199f](https://github.com/Probesys/agentj/commit/ab3199f), [b3e0cda](https://github.com/Probesys/agentj/commit/b3e0cda))

### Technical

- Mutualize ClamAV instance for both amavis and allow to use external ClamAV service ([cf9ffd7](https://github.com/Probesys/agentj/commit/cf9ffd7))
- Store information of all (non quarantined) mails with Amavis ([02ded11](https://github.com/Probesys/agentj/commit/02ded11))
- Allow TLS for Postfix SMTP client ([d11ddc2](https://github.com/Probesys/agentj/commit/d11ddc2))
- Wait for `db` container to be healthy ([f2c3ffa](https://github.com/Probesys/agentj/commit/f2c3ffa))
- Auto-upgrade MariaDB if needed ([098d367](https://github.com/Probesys/agentj/commit/098d367))
- Fix the "amavis user does not exists" error when starting the `app` container ([da493bd](https://github.com/Probesys/agentj/commit/da493bd))

### Developers

- Configure PHPStan up to level 6 ([b64377d](https://github.com/Probesys/agentj/commit/b64377d), [9c2784d](https://github.com/Probesys/agentj/commit/9c2784d))
- Update the app dependencies ([acbf595](https://github.com/Probesys/agentj/commit/acbf595))
- Execute the tests on the host ([d653231](https://github.com/Probesys/agentj/commit/d653231))
- Add brief documentation about `senderverifmilter` ([8329f96](https://github.com/Probesys/agentj/commit/8329f96))

## 2025-03-13 - 2.1.1

### Bug fixes

- List outgoing emails in the advanced search ([#165](https://github.com/Probesys/agentj/pull/165)) ([963d1f1](https://github.com/Probesys/agentj/commit/963d1f1))
- Fix the action order on untreated and spam messages pages ([#164](https://github.com/Probesys/agentj/pull/164)) ([4b96b98](https://github.com/Probesys/agentj/commit/4b96b98))
- Don't lookup MX from outsmtp for configured domains ([#158](https://github.com/Probesys/agentj/pull/158)) ([3ded4b9](https://github.com/Probesys/agentj/commit/3ded4b9))

### Technical

- Update MariaDB to 10.11 (LTS) ([#161](https://github.com/Probesys/agentj/pull/161)) ([baa29fe](https://github.com/Probesys/agentj/commit/baa29fe))
- Fix the link to the Azure OAuth documentation in env file ([#156](https://github.com/Probesys/agentj/pull/156)) ([5d64961](https://github.com/Probesys/agentj/commit/5d64961))

### Developers

- Update the dependencies and remove axios ([#163](https://github.com/Probesys/agentj/pull/163)) ([c75df4e](https://github.com/Probesys/agentj/commit/c75df4e))
- Remove the useless service worker ([#162](https://github.com/Probesys/agentj/pull/162)) ([40b9ac8](https://github.com/Probesys/agentj/commit/40b9ac8))

## 2025-02-28 - 2.1.0

### New

- Allow actions from reports if not logged in ([#134](https://github.com/Probesys/agentj/pull/134)) ([a79df0f](https://github.com/Probesys/agentj/commit/a79df0f))

### Security

- Fix access control for local administrators ([f3e6327](https://github.com/Probesys/agentj/commit/f3e6327), [fa803b1](https://github.com/Probesys/agentj/commit/fa803b1), [17405c0](https://github.com/Probesys/agentj/commit/17405c0))
- Optimize advanced search and secure search from injection ([#141](https://github.com/Probesys/agentj/pull/141)) ([4b69242](https://github.com/Probesys/agentj/commit/4b69242))
- Encrypt and decrypt tokens validity time ([c1f7202](https://github.com/Probesys/agentj/commit/c1f7202))

### Bug fixes

- Restore domain logic for user form ([#140](https://github.com/Probesys/agentj/pull/140)) ([6939a4a](https://github.com/Probesys/agentj/commit/6939a4a))
- Add failsafe for domains without domainkeys ([#118](https://github.com/Probesys/agentj/pull/118)) ([e2d75ed](https://github.com/Probesys/agentj/commit/e2d75ed))
- Fix search function for messages and wblist to search only in displayed columns ([#136](https://github.com/Probesys/agentj/pull/136)) ([bcdf6ed](https://github.com/Probesys/agentj/commit/bcdf6ed))
- Add required field in DomainType to avoid error 500 ([#137](https://github.com/Probesys/agentj/pull/137)) ([33bc617](https://github.com/Probesys/agentj/commit/33bc617))
- Add missing row in policy form and ordered them ([#138](https://github.com/Probesys/agentj/pull/138)) ([695d35c](https://github.com/Probesys/agentj/commit/695d35c))
- Handle admin users with no email ([c988e58](https://github.com/Probesys/agentj/commit/c988e58))
- Require the name in the policy form ([68bf352](https://github.com/Probesys/agentj/commit/68bf352))
- Ensure sender/domain match ([#108](https://github.com/Probesys/agentj/pull/108)) ([75a6344](https://github.com/Probesys/agentj/commit/75a6344))
- Parse the "from" addresses to handle character `+` ([#129](https://github.com/Probesys/agentj/pull/129)) ([bbbcaef](https://github.com/Probesys/agentj/commit/bbbcaef))
- Fix visual issues in homepage modals ([#135](https://github.com/Probesys/agentj/pull/135)) ([b69cf80](https://github.com/Probesys/agentj/commit/b69cf80))
- Fix and add translations in multiple views ([1594623](https://github.com/Probesys/agentj/commit/1594623))

### Documentation

- Merge the old documentation in this repo ([#126](https://github.com/Probesys/agentj/pull/126)) ([493154a](https://github.com/Probesys/agentj/commit/493154a))
- Add basic templates for issues ([#101](https://github.com/Probesys/agentj/pull/101)) ([4c0c8f3](https://github.com/Probesys/agentj/commit/4c0c8f3))
- Add a pull request template ([#102](https://github.com/Probesys/agentj/pull/102)) ([fe8b628](https://github.com/Probesys/agentj/commit/fe8b628))

### Technical

- Remove logspout & some syslog daemons ([#119](https://github.com/Probesys/agentj/pull/119)) ([112251c](https://github.com/Probesys/agentj/commit/112251c))
- Hold over quota mail ([#149](https://github.com/Probesys/agentj/pull/149)) ([12c5d0b](https://github.com/Probesys/agentj/commit/12c5d0b))
- Update the dependencies ([9e67d5e](https://github.com/Probesys/agentj/commit/9e67d5e), [e69ae91](https://github.com/Probesys/agentj/commit/e69ae91), [f9ea75f](https://github.com/Probesys/agentj/commit/f9ea75f))

### Developers

- Setup the CI on GitHub
    - Provide workflows to run PHPStan and testmail ([#86](https://github.com/Probesys/agentj/pull/86)) ([490f342](https://github.com/Probesys/agentj/commit/490f342))
    - Add senderverifmilter ([241560d](https://github.com/Probesys/agentj/commit/241560d))
    - Build and push docker images ([#87](https://github.com/Probesys/agentj/pull/87)) ([b8db71d](https://github.com/Probesys/agentj/commit/b8db71d))
- Add Mailpit to the development environment ([#127](https://github.com/Probesys/agentj/pull/127)) ([0166dc8](https://github.com/Probesys/agentj/commit/0166dc8))
- Extract and refactor a MessageService ([5ff2987](https://github.com/Probesys/agentj/commit/5ff2987))

## v2.0.0

This release brings a main new feature: outgoing mails management. AgentJ
can now be set as relay of an SMTP server, which allows to
- configure send quota (for domains, groups or users)
- prevent unwanted sent mail: spams, virus, eg by a compromised mail account
- get mail alerts for quota overrun/blocked outgoing spam or virus
- get statistics about sent/received mails

Others improvments/bugfixes have been made, details below.

Improvments

* preview untreated mails
* handle outgoing mails: AgentJ can now be set as a relay on a SMTP server
* block outgoing virus/spam (with a different amavis configuration than incoming mails)
* send quota: domain administrator can configure quota for domains, groups or users
* alerts by mail and/or on dashboard when an internal user send a virus, a spam or exceed a quota
* sending a mail to an unknow mail address automatically add it to «Authorized senders»
* improved dashboard: show statistics about received & sent mails
* new search page to find messages by amavis score, sender, receiver …
* show DKIM public keys (DNS format) for domains
* many UI improvments

Bug fixes

* prevent loss of encryption token when restarting app
* unified date format in different views
* fixed language switch
* security fixes

Technical

* upgrade to Symfony 7
* new dev setup, easily configurable in .env
* add basic tests for sent and received mails (block unknow users/virus/spam, in both way; allow an expeditor by sending a mail; quota)
* unification of authentication methods (IMAP/LDAP/M365)
* all containers are now based on Debian 12
* DKIM keys are now generated via PHP and stored in the database

> [!CAUTION]
> **Before** the upgrade, extract DKIM keys and Symfony tokens from the running instance (see [helper script](scripts/get-dkim-sf-encryption-token-pre-agentj2.sh))
>
> - add Symfony tokens to `.env` (in addition to merge changes from `.env.example`)
> - pull and start the new containers
> - wait for the end of database migrations, then insert the previously saved DKIM keys
> - run `docker compose exec db /docker-entrypoint-initdb.d/opendkim_user.sh` to create the new opendkim user
> - ensure nginx log folder is ok `docker compose exec app 'mkdir -p /var/log/nginx ; chown -R www-data:www-data /var/log/nginx'`

## v1.6.4

If you use oauth authentication you need to create an Office365 connector, configure it with your Microsoft Entra application and synchronize agentj users.

## v1.5.3

This version improves the way virus mail are handled and displayed in the Web interface. There is now a dedicated section for such mails in which they cannot be released (for security reasons, if releasing the mail is legitimate it should be done manually on the server side by an admin).
It also adds the ability to use a remote ClamAV server so you **must** add the following variables in your `.env` file:

```
CLAMAV_AUTOSTART=true
CLAMAV_TCPADDRESS=0.0.0.0
CLAMAV_TCPPORT=3310
```

By default, the *clamav* service, installed in the *amavis* container will start. If CLAMAV_AUTOSTART is set to *false*, it won't start and *amavis* will try to connect ClamAV at the IP address configured in CLAMAV_TCPADDRESS.

## v1.5.0

This version adds OAuth with Azure, please refer to [dedicated deployement documentation](/docs/administrators/auth_azure.md) if you need this feature.

## v1.4.3

This version improves the startup process of critical components and removes some volumes.

### Changes in Amavis

In the *amavis* service, the following volumes have been removed:

* clamav
* spamassassin

The signatures databases of these services are now generated during image build time. This improves the startup when no Interne connection is available.

### Changes in openDKIM

The *opendkim* configuration is now static and embedded in the `app` image. The keys volume now points to `/var/db/dkim` its name remains unchanged (`opendkim`).

* In file `KeyTable` of this volume, you need to change `/etc/opendkim` to `/var/db/dkim` at beginning of each key path.

### Changes in database

This version changes mariadb version:

* launch following commands (`DB_ROOT_PASSWORD` is defined in your `.env` file):
```bash
docker-compose exec db /bin/bash
mariadb-upgrade --password=<DB_ROOT_PASSWORD>
```

### Others changes

The `postfix_relay` volume has been removed. The *postfix* configuration is now static and embedded in their respective images. This will make the future changes and upgrades easier and safer.
The *watchtower* has been removed as it was not useful, upgrades often need manual changes.

### Upgrade process

Change $VERSION variable in your `.env` file.

```
docker-compose down
docker volume remove $COMPOSE_PROJECT_NAME_clamav $COMPOSE_PROJECT_NAME_postfix_relay $COMPOSE_PROJECT_NAME_smtpconfig $COMPOSE_PROJECT_NAME_spamassassin
docker-compose up -d
```

## v1.4.2

This version add *SPF* checks to postfix and DKIM keys generation when adding a domain using web UI.

* modifications on `smtp` container

!!! important
    If you have made custom changes to `smtpconfig` volume, you have to merge default config manually.

Unless you have made custom changes in `main.cf` and `master.cf` files in `smtpconfig` volume, you can safely remove it:

    docker volume rm '<COMPOSE_PROJECT_NAME>_smtpconfig'

* modifications on `app` container

You must launch following commands once `app` container is running:

    docker-compose exec app /bin/sh
    chown -R opendkim: /etc/opendkim/
    echo "RequireSafeKeys         false" >> /etc/opendkim/opendkim.conf


## v1.4.0

* New `.env.example` file, modify your `.env` file:
    * Add `VERSION` variable
    * Check `ADMIN_PASSWORD` variable name (was `ADMIN_password`)
* The *public* volume, shared between former `web` container and `app` container is not needed anymore

```
docker volume rm agentj-docker_public
```

## v1.3.2

!!! important
    If you have made custom changes to `smtpconfig` volume, you have to merge default config manually.

Configuration of `smtp` container has been changed, unless you have made custom changes in `main.cf` and `master.cf` files in `smtpconfig` volume, you can safely remove it:

    docker volume rm '<COMPOSE_PROJECT_NAME>_smtpconfig'

Launch following commande in `smtp` container:

    chown postfix -R /var/spool/postfix/

MariaDB image is now 10.7.3, an upgrade of database is needed. Once your container is running, launch following commands:

```bash
docker-compose exec db /bin/bash
mariadb-upgrade
```

## v1.3.1

Nothing specific.

## v1.3.0

Variable `COMPOSE_PROJECT_NAME=docker` was added in `.env` file.
Its value must correspond to volumes already present for your AgentJ docker stack.

## Upgrade from version 1.0.6 to 1.2+

Newer version of AgentJ (starting from 1.2) introduce breaking changes such as:

- change in the underlying base images (Alpine instead of Debian slim) and some changes in database structure;
- Amavis does not use the same directories structure to store the mails and logs are sort differently;
- new initialisation and migration process for the database;
- new variables are used in the `.env` file.

### Database migration

Stop the entire stack:

    docker-compose down

Start only the database:

    docker-compose up -d db

Connect to the database:

    docker-compose exec db sh
    mysql -uroot -p
    # Type password

Execute the following:

    CREATE TABLE doctrine_migration_versions ( version varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL, executed_at datetime DEFAULT NULL, execution_time int(11) DEFAULT NULL, PRIMARY KEY (version) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
    insert into doctrine_migration_versions (version, executed_at, execution_time) values ('DoctrineMigrations\\Version20211221152434',null, 0);
    exit;

Exit the container (Ctrl + D)

### Moving the volumes content

It is recommended to prefix old volumes name with something like `old` during the migration process to preserve their content. This can also be done by setting the `COMPOSE_PROJECT_NAME` variable (in `env.` file) to something different and by starting the new stack with it.

#### Amavis

Copy content of `tmp` to `tmp` in new volume as well as `virusmails` folder.

#### MariaDB

Copy content of the old `db` volume to the new one.

#### Logs

Move or copy the relevant logs to the new volume.

#### openDKIM

Copy the whole content of the old volume to the new one.

#### Postfix: configuration of relay container

If changes have been made to the default configuration, the relevant `main.cf` and/or `master.cf` files must be copied from one volume to the other.

#### Postfix: configuration of smtp container (smtpconfig volume)

If changes have been made to the default configuration, the relevant `main.cf` and/or `master.cf` files must be copied from one volume to the other.

### Fix ownership and rights

User names and ids are different between Debian and Alpine, so rights and ownership must be fixed:

| Volume                  | Old user id | Old group id | New user id | New group id |
|-------------------------|-------------|--------------|-------------|--------------|
| amavis                  | 102         | 103          | 100         | 101          |
| opendkim (keys folder ) | 102         | 104          | 100         | 101          |

## v1.2.3

* clamav: improve start process, move it to entrypoint
* amavis: update clamav run dir
* syslogng: improve log format, add rotation cron
* app: remove duplicate cron entries

## v1.2.1

* fix csv import emails

## v1.2.0

* upgrade to Symfony 5
* upgrade Alpine base
* database initialisation process improvement
