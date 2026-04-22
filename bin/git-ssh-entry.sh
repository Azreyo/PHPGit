#!/bin/bash
exec /bin/php8.4 \
    -d opcache.enable=0 \
    -d opcache.enable_cli=0 \
    "$(dirname "$0")/git-shell-wrapper.php" "$@" \
    2> >(grep -v "^Cannot load Zend OPcache" >&2)


