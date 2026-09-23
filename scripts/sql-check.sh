echo 'Check if SQL files are staged'

# Check if a whitelist file exists
SQL_WHITELIST=".sql-whitelist"
if [ -f "$SQL_WHITELIST" ]; then
    # Get all SQL files in the commit
    all_sql_files=$(git diff --name-only --cached -- '*.sql')
    
    # Initialize an empty string for non-whitelisted files
    non_whitelisted_sql_files=""
    
    # Loop through each SQL file
    for file in $all_sql_files; do
        # Check if the file matches any pattern in the whitelist
        if ! grep -q -f "$SQL_WHITELIST" <<< "$file"; then
            non_whitelisted_sql_files="$non_whitelisted_sql_files$file"
        fi
    done
    
    # Remove trailing newline
    non_whitelisted_sql_files=$(echo "$non_whitelisted_sql_files" | sed '/^$/d')
    
    # Use non-whitelisted files for the warning
    sql_files="$non_whitelisted_sql_files"
else
    # If no whitelist exists, check all SQL files
    sql_files=$(git diff --name-only --cached -- '*.sql')
fi

if [[ $(echo -n "$sql_files" | wc -c) != 0 ]]
then
    echo "[WARNING] You are trying to commit one or more SQL files. Please check that these files are legitimate and don't contain any personal information."
    echo "$sql_files"

    read -p "Are these files legitimate? (y/N) " choice < /dev/tty

    if [[ ! $choice =~ ^[Yy]$ ]]
    then
        echo "Commit aborted"
        exit 1
    fi
fi
