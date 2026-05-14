#!/bin/sh

# Override php.ini settings from environment
{
  echo "opcache.enable=${PHP_OPCACHE_ENABLE:-0}"
  echo "display_errors=${PHP_DISPLAY_ERRORS:-On}"
  echo "error_reporting=${PHP_ERROR_REPORTING:-E_ALL}"
} > /usr/local/etc/php/conf.d/env-overrides.ini

exec docker-php-entrypoint "$@"