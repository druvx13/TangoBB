#!/bin/bash

# Set working directory to /app where the codebase is.
cd /app

echo "Starting PHP syntax and feature updates (excluding each() replacement)..."

# Backup files before modification (important if running this standalone)
BACKUP_DIR="/tmp/php_backups_$(date +%Y%m%d%H%M%S)"
mkdir -p "${BACKUP_DIR}"
echo "Backup directory: ${BACKUP_DIR}"
find . -name "*.php" -exec sh -c 'mkdir -p "${1%/*}" && cp "{}" "$1"' _ "${BACKUP_DIR}/\{\}" \;
echo "Files backed up to ${BACKUP_DIR}"

echo "Skipping automated each() replacement due to previous errors with sed."

echo "Addressing mysql_* functions..."
echo "Files using mysql_* functions (manual refactoring required):"
# Exclude backup directory from grep results
BACKUP_DIR_PATTERN="php_backups" # General pattern to exclude any backup dir
grep -rl "mysql_.*(" --include=\*.php . | grep -vE "mysql_xdevapi|${BACKUP_DIR_PATTERN}" > /tmp/mysql_files.txt
cat /tmp/mysql_files.txt
echo "Note: Manual refactoring of mysql_* functions is required. The above list identifies affected files."

echo "Checking for implode() parameter order (manual review needed based on audit report)..."
# No automated replacement for implode.

echo "Removing FILTER_FLAG_SCHEME_REQUIRED and FILTER_FLAG_HOST_REQUIRED..."
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i -E 's/FILTER_VALIDATE_URL\s*,\s*([A-Z0-9_]+\s*\|\s*)?(FILTER_FLAG_SCHEME_REQUIRED\s*\|\s*FILTER_FLAG_HOST_REQUIRED)(\s*\|\s*[A-Z0-9_]+)?/FILTER_VALIDATE_URL/g'
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i -E 's/FILTER_VALIDATE_URL\s*,\s*([A-Z0-9_]+\s*\|\s*)?FILTER_FLAG_SCHEME_REQUIRED(\s*\|\s*[A-Z0-9_]+)?/FILTER_VALIDATE_URL/g'
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i -E 's/FILTER_VALIDATE_URL\s*,\s*([A-Z0-9_]+\s*\|\s*)?FILTER_FLAG_HOST_REQUIRED(\s*\|\s*[A-Z0-9_]+)?/FILTER_VALIDATE_URL/g'
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i -E 's/filter_var\(([^,]+,\s*FILTER_VALIDATE_URL)\s*,\s*\)/filter_var(\1)/g'
# Cleanup any remaining "..., 0)" from simpler previous attempts if any
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i 's/FILTER_VALIDATE_URL\s*,\s*0\s*\)/FILTER_VALIDATE_URL\)/g'


echo "Addressing mcrypt_encrypt (manual review needed for alternatives like OpenSSL or Sodium)..."
echo "Files using mcrypt_encrypt (manual refactoring required):"
grep -rl "mcrypt_encrypt" --include=\*.php . | grep -vE "${BACKUP_DIR_PATTERN}" > /tmp/mcrypt_files.txt
cat /tmp/mcrypt_files.txt
echo "Note: Manual refactoring of mcrypt_encrypt is required. The above list identifies affected files."

echo "Addressing get_magic_quotes_runtime() in PHPMailer (likely needs library update or patch)..."
echo "File potentially using get_magic_quotes_runtime(): applications/libraries/phpmailer/class.phpmailer.php"
# No automated change for get_magic_quotes_runtime() itself.

echo "Addressing ini_set for magic_quotes_runtime (directive removed)..."
echo "Removing ini_set('magic_quotes_runtime', ...);"
# Changed to single quotes for consistency and to avoid shell interpretation issues with backslashes.
# Added -E for consistency, though for this simple pattern it might not be strictly needed.
# The /d command in sed doesn't use the s/// structure, so no delimiters issue.
# Added case-insensitivity 'I' at the end of the pattern.
find . -path ./vendor -prune -o -name "*.php" -type f -print0 | xargs -0 sed -i -E "/ini_set\s*\(\s*['\"]magic_quotes_runtime['\"].*\);/I d"

echo "PHP syntax and feature updates script finished (excluding each() replacement)."
echo "Please review the changes carefully, especially for mysql_* and mcrypt_encrypt which require manual refactoring."
echo "Original files are backed up in ${BACKUP_DIR}"
