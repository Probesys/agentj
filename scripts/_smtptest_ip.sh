#!/bin/bash

docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$(docker compose ps -q smtptest)"
