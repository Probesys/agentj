# PHPStorm

## Configuration

### Server

* Open settings PHP -> Server
    * `name`: docker, `port`: 8090, `debugger`: Xdebug
    * Check `Use path mapping`
    * Add `/path/to/agentj/app` = `/var/www/agentj`
    * Save and close
* On toolbar, click on the 3 dots to edit configurations
* On the left bottom corner, click on `Edit configuration templates`
    * Docker
        * Select `Docker` -> `Docker Compose`
        * Choose `docker` as server
        * Set `./compose.dev.yml` in compose files
        * Save
    * PHP Remote Debug
        * Check `Filter debug connection by IDE key`
        * Choose `docker` as server
        * Save
    * PHPUnit
        * For test runner choose `Defined in the configuration file`
        * Command Line -> Interpreter: choose `app`
        * Then click on the 3 dots
        * Lifecycle -> check `Connect to existing container`
        * Save
