#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RED='\033[1;31m'
GREEN='\033[1;32m'
YELLOW='\033[1;33m'
NC='\033[0m'
SAFE_LIST_FILE="${SCRIPT_DIR}/safe-files.txt"
PATTERNS=("*\.sql" "*\.dump*" "*\.env")
DANGEROUS_FILES=()

is_in_safe_list() {
    local file="$1"
    while IFS= read -r safe_file; do # IFS -> read the name with spaces || for every line in the safe list
        [[ -z "$safe_file" || "$safe_file" =~ ^# ]] && continue #ignore comments and empty lines

        if [[ "$safe_file" =~ /$ ]]; then # if the line describes a folder
            # Vérifier si le fichier est dans ce dossier
            if [[ "$file" == ${safe_file}* ]]; then
                return 0
            fi
        else
            # Comparaison normale avec support des wildcards
            if [[ "$file" == $safe_file ]]; then
                return 0
            fi
        fi
    done < "$SAFE_LIST_FILE"
    return 1
}

echo ""
echo -e "${YELLOW}Checking for sensitive files...${NC}"
echo ""

for pattern in "${PATTERNS[@]}"; do # for all files detected by the pattern
    while IFS= read -r file; do # read the safe list
        [[ -z "$file" || -d "$file" ]] && continue #stop if it's a directory or an empty line
        if ! is_in_safe_list "$file"; then # is the file in the safe list
            DANGEROUS_FILES+=("$file")
        fi
    done < <(git ls-files | grep -E "${pattern//\*/.*}")
done

if [ ${#DANGEROUS_FILES[@]} -eq 0 ]; then
    echo -e "${GREEN}No dangerous files detected!${NC}"
    echo ""
    exit 0 # pipeline ok
else
    # remove duplicates before displaying
    IFS=$'\n' DANGEROUS_FILES=($(sort -u <<<"${DANGEROUS_FILES[*]}"))
    unset IFS

    echo -e "${RED}(${#DANGEROUS_FILES[@]}) Dangerous files detected:${NC}"
    for file in "${DANGEROUS_FILES[@]}"; do
        echo -e "${RED}${NC}$file"
    done
    echo ""
    echo -e "${RED} Pipeline blocked | to solve this issue, remove these items or add them to:${NC}"
    echo -e "   ${SAFE_LIST_FILE}"
    echo ""
    exit 1 # pipeline failed
fi
