#!/usr/bin/env bash

_APP="AMAVISD"
_PORT=10024

if [ ! -z "$(ss -Hl '( sport = :10024 )')" ] ; then
	echo "$_APP: listening on port $_PORT"
else
	echo "$_APP: not listening, check logs for errors"
	exit 1
fi
