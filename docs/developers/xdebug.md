# Working with Xdebug

Xdebug is a PHP extension that enables advanced debugging capabilities. It allows you to set breakpoints, inspect variables at runtime, etc. All from your IDE.

> [!NOTE]
> Xdebug is **already installed** in the development `app` Docker image provided by this project. You can see the Xdebug configuration in [`app/docker/files/docker-php-ext-xdebug.ini`](/app/docker/files/docker-php-ext-xdebug.ini)

## Common behavior

* Place breakpoints and start debugging!
* Open your navigator and trigger it by sending a request with the argument `XDEBUG_SESSION_START`
    * e.g. [http://localhost:8090?XDEBUG\_SESSION\_START=1](http://localhost:8090?XDEBUG_SESSION_START=1)

## Debug in VS Code

See [VSCode documentation](./vscode.md) for configuration.

## Debug in PHPStorm

See [PHPStorm documentation](./phpstorm.md) for configuration.

Once configured, start the debug session in PHPStorm.
