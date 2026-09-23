#!/usr/bin/bash

# Git default hooks names
pre_commit_hook_file="pre-commit"
pre_push_hook_file="pre-push"

# Git default hooks path
hooks_directory=".git/hooks"

pre_commit_script="#!/usr/bin/env bash
set -e
echo 'Pre-commit hook'
make lint LINTER=phpstan
$(cat ./scripts/sql-check.sh)
"

pre_push_script="#!/usr/bin/env bash
set -e
echo 'Pre-push hook'
make lint
make test
"

echo "$pre_commit_script" > "$pre_commit_hook_file"
echo "$pre_push_script" > "$pre_push_hook_file"

chmod +x "$pre_commit_hook_file"
chmod +x "$pre_push_hook_file"

mv "$pre_commit_hook_file" "$hooks_directory/pre-commit"
mv "$pre_push_hook_file" "$hooks_directory/pre-push"

echo "Hooks installed successfully!"
