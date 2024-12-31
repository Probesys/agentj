#!/bin/bash

echo in
docker compose exec smtp postqueue -p

echo out
docker compose exec outsmtp postqueue -p

echo smtptest
docker compose exec smtptest smtpctl show queue
