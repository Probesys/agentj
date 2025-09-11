# Install the development environment

Get the code:

```console
$ git clone git@github.com:Probesys/agentj.git
$ cd agentj
```

Start the application:

```console
$ make docker-start
```

The command automatically setup an `.env` file suitable for development.

Load test data into your database with (this command **will not** delete existing data):

```console
$ scripts/console doctrine:fixtures:load --append
```

Login to [localhost:8090](http://localhost:8090) with the credentials `admin` / `secret`.

## Working in the Docker containers

There are few scripts to allow to execute commands in the Docker containers easily:

```
$ ./scripts/php
$ ./scripts/composer
$ ./scripts/console
$ ./scripts/yarn
$ ./scripts/mariadb
```

## Update the Docker images

You can rebuild and pull the images manually with:

```console
$ make docker-images
```

## Clean your Docker environment

Sometimes, you may need to rebuild your environment from scratch.
You can remove all the Docker stuff (containers, volumes, networks) with:

```console
$ make docker-clean FORCE=true
```

## About the `compose.dev.yml` file

This file is used to override the Docker Compose configuration in development:

- it starts [Mailpit](https://mailpit.axllent.org)
- it mounts the code from your local `app/` folder into the app container
- it exposes the database port to the host
- it sets static IPs and specify DNS for some containers
