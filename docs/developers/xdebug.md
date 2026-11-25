# Working with Xdebug

Xdebug is a PHP extension that enables advanced debugging capabilities. It allows you to set breakpoints, inspect variables at runtime, etc. All from your IDE.

> [!NOTE]
> Xdebug is **already installed** in the development `app` Docker image provided by this project. You can see the Xdebug configuration in [`app/docker/files/docker-php-ext-xdebug.ini`](/app/docker/files/docker-php-ext-xdebug.ini)

## Common behavior

* Place breakpoints and start debugging!
* Open your navigator and trigger it by sending a request with the argument `XDEBUG_SESSION_START`
    * e.g. [http://localhost:8090?XDEBUG\_SESSION\_START=1](http://localhost:8090?XDEBUG_SESSION_START=1)

## Debug commands, cron jobs and the worker

In the `app` and `worker` containers, all the PHP commands (including Composer, PHPUnit, etc.) try to connect to your IDE when they start. If your IDE isn't listening, they are executed normally.

> [!NOTE]
> You can see the configuration in [`app/docker/files/docker-php-ext-xdebug-cli.ini`](/app/docker/files/docker-php-ext-xdebug-cli.ini). Rebuild the images if you change it.

To execute a command without debugging while your IDE is listening, disable Xdebug:

```console
$ docker compose exec -u www-data -e XDEBUG_MODE=off app php bin/console agentj:send-report-mail
```

### Debug a command

Place your breakpoints, start listening in your IDE, then execute the command:

```console
$ docker compose exec -u www-data app php bin/console agentj:send-report-mail
```

### Debug a cron job

Place your breakpoints, start listening in your IDE, then wait for cron to execute the job. The jobs are listed in [`app/docker/conf/cron/agentj`](/app/docker/conf/cron/agentj).

Cron doesn't wait for you: a job scheduled every minute starts again while you're stopped on a breakpoint, and opens a new debugging session. In VS Code, you can set `"maxConnections": 1` in your `launch.json` file to ignore the additional sessions. In this case, stop the worker first (`docker compose stop worker`), or it will use the only connection available.

### Debug the worker (Messenger and Scheduler)

The worker connects to your IDE only when it starts, and Xdebug ignores the breakpoints added while the code is running. The order is important:

1. Place your breakpoints.
2. Start listening in your IDE.
3. Restart the worker: `docker compose restart worker`.
4. Trigger the action to debug (e.g. synchronize a LDAP connector).

Restart the worker each time you add a breakpoint (unless it's already stopped on another one), or if you restart your IDE listener.

### Troubleshooting

If your IDE is listening but the execution never stops, display the Xdebug connection errors:

```console
$ docker compose exec worker php -d xdebug.log_level=7 -r ';'
```

A "Time-out connecting to debugging client" message usually means that the firewall of your machine drops the connections coming from Docker. It also slows down each PHP command by 200 ms. For instance, if you use ufw:

```console
$ sudo ufw allow from 172.28.0.0/16 to any port 9003 proto tcp
```

## Debug in VS Code

See [VSCode documentation](./vscode.md) for configuration.

## Debug in PHPStorm

See [PHPStorm documentation](./phpstorm.md) for configuration.

Once configured, start the debug session in PHPStorm.
