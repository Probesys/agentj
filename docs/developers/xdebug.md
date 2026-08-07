# Working with Xdebug

Xdebug is a PHP extension that enables advanced debugging capabilities. It allows you to set breakpoints, inspect variables at runtime, etc. All from your IDE.

> [!NOTE]
> Xdebug is **already installed** in the development `app` Docker image provided by this project. You can see the Xdebug configuration in [`app/docker/files/docker-php-ext-xdebug.ini`](/app/docker/files/docker-php-ext-xdebug.ini)

## Common behavior

* Place breakpoints and start debugging!
* Open your navigator and trigger it by sending a request with the argument `XDEBUG_SESSION_START`
    * e.g. [http://localhost:8090?XDEBUG\_SESSION\_START=1](http://localhost:8090?XDEBUG_SESSION_START=1)

## Debug in VS Code

1. Install the **PHP Debug** extension in VS Code by pressing <kbd>CTRL + P</kbd> and enter the following command: `ext install xdebug.php-debug`;
2. Go to **Run and Debug**, add a new configuration for PHP debugging, then replace the file `.vscode/launch.json` by the following:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/agentj/": "${workspaceFolder}/app"
            }
        }
    ]
}
```
3. Start the debug session in VS Code

## Debug in PHPStorm

See [PHPStorm documentation](./phpstorm.md) for configuration.

Once configured, start the debug session in PHPStorm.
