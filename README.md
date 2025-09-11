<h1 align="center">AgentJ</h1>

<p align="center">
    <strong>Libre antispam system with human authentication</strong><br>
    <a href="https://agentj.io/">agentj.io</a>
</p>

---

**AgentJ** is designed to protect your mail server from malicious messages and to prevent compromised accounts to send spam or virus.

Features includes:
- human authentication for unknown senders
- spam/virus detection, for received and sent messages
- quota, shared mailbox, aliases

It runs in a Docker stack and use well-known projects and technologies:
- mail system: Postfix, Amavis, SpamAssassin, ClamAV, OpenDKIM
- web interface: Symfony 7 with PHP 8.2 and MariaDB

AgentJ is a [Probesys](https://www.probesys.coop) project, made available under the GNU Affero General Public License v3.0.

## Documentation

[Read the documentation.](docs/README.md)
