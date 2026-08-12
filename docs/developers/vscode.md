# Lauching tests from VSCode (VSCodium actually)

## Configuration

1. Install the **PHP Debug** extension in VS Code by pressing <kbd>CTRL + P</kbd> and enter the following command: `ext install xdebug.php-debug`
2. Install **PHPUnit & Pest Test Explorer** extension by pressing <kbd>CTRL + P</kbd> and enter the following command: `ext install recca0120.vscode-phpunit`
3. Go to **Run and Debug**, add a new configuration for PHP debugging, then replace the file `.vscode/launch.json` by the following:
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
4. Add to `.vscode/settings.json` :
```
{
  // … Your existing configuration … 

  "phpunit.debuggerConfig": "Listen for Xdebug",
  "phpunit.xdebugPort": 9003,
  "phpunit.command": "docker compose exec -t -e XDEBUG_TRIGGER=1 app php -dxdebug.client_host=host.docker.internal ${phpargs} ${phpunit} ${phpunitargs}",
  "phpunit.paths": {
    "/host/path/to/agentj/app": "/var/www/agentj"
  }
}
```
You have to use the full path to the app code folder, as `${workspaceFolder}` does not seam to work.

If VSCode / VSCodium has been installed using Flatpak, the command is different:
```
"phpunit.command": "flatpak-spawn --host docker compose exec -t -e XDEBUG_TRIGGER=1 app php -dxdebug.client_host=host.docker.internal ${phpargs} ${phpunit} ${phpunitargs}",
```

5. Place breakpoints in code
6. Launch test(s) in debug
7. Debug session is automatically started

## Play tests

### From file explorer

1. Right clic on test file or folder.
2. Clic on `Run Tests`.
3. `TEST RESULTS` view is focused on bottom of IDE.

### From file

1. Clic on the green icon beside class or function that define test(s).
2. `TEST RESULTS` view is focused on bottom of IDE.

## Debug tests

Place breakpoints in code, then launch tests in debug

1. Right clic on test file or folder.
2. Clic on `Debug Tests`.
3. `TEST RESULTS` view is focused on bottom of IDE.

### From file

1. Right clic on the green icon beside class or function that define test(s) and choose `Debug Test`.
2. `TEST RESULTS` view is focused on bottom of IDE.

