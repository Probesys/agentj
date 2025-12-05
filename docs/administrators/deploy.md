# Deploy in production

## Installation

Get the code:

```console
$ git clone https://github.com/Probesys/agentj.git
$ cd agentj
```

Checkout to the latest version (see [releases](https://github.com/Probesys/agentj/releases)):

```console
$ git checkout <VERSION>
```

Configure the application:

```console
$ cp .env.example .env
```

Edit the `.env` file to your needs.
The file is commented to help you to change it.

Then start the Docker stack:

```console
$ docker compose up
```

> [!CAUTION]
> It is not recommended to launch the stack as root.
> We recommend you to create a dedicated `docker` user (make sure it belongs to the `docker` group).

You can then access the web interface to configure your email domain.

## Optional: Run several workers

If the application starts to struggle with processing the asynchronous tasks (e.g. releasing Amavis messages or sending emails), you can run more workers in parallel using this command:

```console
$ docker compose up -d --scale worker=X
```

Where `X` is the number of workers to run.

## Upgrade

> [!IMPORTANT]
> Always backup your data (database and volumes) and check the [changelog](/CHANGELOG.md) **before** beginning the upgrade process.
> Breaking changes are highlighted in the migration notes for each version.

Update the code to the latest version (see [releases](https://github.com/Probesys/agentj/releases)):

```console
$ git fetch
$ git checkout <VERSION>
```

Update the `VERSION` variable in the `.env` file and adapt other variables accordingly to the migration notes.

Restart the stack:

```console
$ docker compose pull
$ docker compose up -d
```
