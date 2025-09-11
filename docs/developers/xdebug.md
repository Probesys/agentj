# Working with Xdebug

Xdebug is a PHP extension that enables advanced debugging capabilities. It allows you to set breakpoints, inspect variables at runtime, etc. All from your IDE.

> [!NOTE]
> Xdebug is **already installed** in the development `app` Docker image provided by this project. You can see the Xdebug configuration in [`app/docker/files/docker-php-ext-xdebug.ini`](/app/docker/files/docker-php-ext-xdebug.ini)

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
3. Start the debug session in VS Code: you can open your navigator and trigger it by sending a request with the argument `XDEBUG_SESSION=agentj`, e.g. [localhost:8090?XDEBUG\_SESSION=agentj](http://localhost:8090?XDEBUG_SESSION=agentj)
4. Place breakpoints and start debugging!
