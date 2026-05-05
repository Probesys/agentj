#!/bin/bash

echo -n 'Learning hams...'
sa-learn --username amavis --showdots --ham /sa_learn/hams

echo -n 'Learning spams...'
sa-learn --username amavis --showdots --spam /sa_learn/spams

echo 'Removing mails...'
rm -f /sa_learn/hams/*
rm -f /sa_learn/spams/*

echo 'Bayesian learning is complete! 🎉'
