#!/bin/bash

echo "Starting PHP 8.0+ incompatibility audit..."

# Create a directory for reports
mkdir -p /tmp/php8_audit_reports

# Common deprecated functions and features to search for.
# This list is not exhaustive but covers many common issues.
# Ensured patterns are escaped for grep -E where necessary.
DEPRECATED_PATTERNS=(
    # PHP 7.0 deprecated
    "ereg_"
    "mysql_.*\\(" # excluding mysql_xdevapi
    "(^|[^a-zA-Z0-9_])split\\s*\\(" # POSIX split, deprecated 5.3, removed 7.0
    "mcrypt_encrypt"
    "mcrypt_decrypt"
    "mcrypt_module_open"
    # PHP 7.1 deprecated
    "mcrypt_generic_init"
    # PHP 7.2 deprecated
    "create_function\\("
    "each\\s*\\(" # More specific 'each'
    "assert\\s*\\(" # with string argument
    "\\(unset\\)\\s*\\(\\s*(object)\\s*\\)" # (unset) cast
    "gmp_random\\("
    "read_exif_data\\(" # alias of exif_read_data
    # PHP 7.3 deprecated
    "image2wbmp\\("
    "ldap_control_paged_result_response\\("
    "ldap_control_paged_result\\("
    # PHP 7.4 deprecated
    "get_magic_quotes_gpc\\("
    "get_magic_quotes_runtime\\("
    # Order of parameters for implode: implode($glue, $pieces) is correct.
    # implode($pieces, $glue) is deprecated.
    # This grep pattern is a best-effort and might catch correct usages too.
    # Manual verification is needed for 'implode' findings.
    # It looks for implode where the first arg is likely an array variable and second is a string literal.
    "implode\\s*\\(\\s*\\$[a-zA-Z_][a-zA-Z0-9_]*\\s*,\\s*['\"]"
    "FILTER_SANITIZE_MAGIC_QUOTES"
    "ReflectionType::isBuiltin\\(" # use ReflectionNamedType::isBuiltin()
    "money_format\\("
    "restore_include_path\\("
    "\\\$php_errormsg" # $php_errormsg variable
    "libxml_disable_entity_loader\\("
    # PHP 8.0 removed or significantly changed
    "\\\$GLOBALS\\['HTTP_RAW_POST_DATA'\\]"
    "xmlrpc_server_create"
    "xmlrpc_decode_request"
    "utf8_decode\\("
    "utf8_encode\\("
    "OCI-Lob::seek\\("
    "OCI-Collection::seek\\("
    # Curly braces for accessing array elements and string offsets {$}
    # This pattern looks for $var{$offset}
    "\\\$[a-zA-Z_][a-zA-Z0-9_]*\\{[^}]+\\}"
    # Other potential issues
    # ":\s*new\s+[a-zA-Z0-9_]+\s*\(" # type hints for constructors - too broad, many false positives
    "ReflectionParameter::getClass\\(\\)"
    "ReflectionParameter::isArray\\(\\)"
    "ReflectionParameter::isCallable\\(\\)"
    # "allow_url_include" # ini directive - handled separately
    "FILTER_FLAG_SCHEME_REQUIRED" # Removed in PHP 8.0, behavior is default
    "FILTER_FLAG_HOST_REQUIRED"   # Removed in PHP 8.0, behavior is default
)

# Files to check (PHP files)
# Excluding ./vendor/ directory from find itself
find . -path ./vendor -prune -o -name "*.php" -type f > /tmp/php_files.txt

# Perform the grep search and save results
REPORT_FILE="/tmp/php8_audit_reports/php8_compatibility_report.txt" # Renamed report file
echo "PHP 8.0+ Incompatibility Report" > "${REPORT_FILE}"
echo "=================================" >> "${REPORT_FILE}"
echo "Date: $(date)" >> "${REPORT_FILE}"
echo "" >> "${REPORT_FILE}"
echo "Searching for deprecated patterns..." >> "${REPORT_FILE}"
echo "---------------------------------" >> "${REPORT_FILE}"


while IFS= read -r file; do
    # Skip files in vendor directories (double check, already excluded in find but good for safety)
    if [[ "$file" == ./vendor/* ]]; then
        continue
    fi
    for pattern in "${DEPRECATED_PATTERNS[@]}"; do
        # Skip mysql_xdevapi for mysql_.*\( pattern
        if [[ "${pattern}" == "mysql_.*\\(" && $(echo "${file}" | grep -q "mysql_xdevapi"; echo $?) -eq 0 ]]; then
            continue
        fi

        # Perform grep
        # The -H option adds filename, -n adds line number
        if grep -q -E -- "${pattern}" "${file}"; then
            echo "" >> "${REPORT_FILE}"
            echo "File: ${file}" >> "${REPORT_FILE}"
            echo "Pattern (Deprecated): ${pattern}" >> "${REPORT_FILE}"
            grep -n -H -E -- "${pattern}" "${file}" >> "${REPORT_FILE}"
            echo "---------------------------------" >> "${REPORT_FILE}"
        fi
    done
done < /tmp/php_files.txt

# Check for removed INI directives
echo "" >> "${REPORT_FILE}"
echo "Potential usage of removed INI directives (via ini_set/ini_get):" >> "${REPORT_FILE}"
echo "---------------------------------" >> "${REPORT_FILE}"
INI_DIRECTIVES_TO_CHECK=(
    "allow_url_include"
    "magic_quotes_gpc"
    "magic_quotes_runtime"
    "magic_quotes_sybase"
)
while IFS= read -r file; do
    if [[ "$file" == ./vendor/* ]]; then
        continue
    fi
    for directive in "${INI_DIRECTIVES_TO_CHECK[@]}"; do
        # Check for ini_set('directive_name', ...)
        if grep -q -i -E -- "ini_set\\s*\\(\\s*['\"]${directive}['\"]\\s*," "${file}"; then
            echo "" >> "${REPORT_FILE}"
            echo "File: ${file}" >> "${REPORT_FILE}"
            echo "Directive (ini_set): ${directive}" >> "${REPORT_FILE}"
            grep -n -H -i -E -- "ini_set\\s*\\(\\s*['\"]${directive}['\"]\\s*," "${file}" >> "${REPORT_FILE}"
            echo "---------------------------------" >> "${REPORT_FILE}"
        fi
        # Check for ini_get('directive_name')
        if grep -q -i -E -- "ini_get\\s*\\(\\s*['\"]${directive}['\"]\\s*\\)" "${file}"; then
            echo "" >> "${REPORT_FILE}"
            echo "File: ${file}" >> "${REPORT_FILE}"
            echo "Directive (ini_get): ${directive}" >> "${REPORT_FILE}"
            grep -n -H -i -E -- "ini_get\\s*\\(\\s*['\"]${directive}['\"]\\s*\\)" "${file}" >> "${REPORT_FILE}"
            echo "---------------------------------" >> "${REPORT_FILE}"
        fi
    done
done < /tmp/php_files.txt

echo "Audit script finished. Report generated at ${REPORT_FILE}"
echo ""
echo "Displaying report:"
echo "================================================================================"
cat "${REPORT_FILE}"
