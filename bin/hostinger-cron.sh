#!/usr/bin/env bash
#
# Hostinger cron entry — run this every minute from hPanel Cron Jobs.
# One cron line processes the queue + daily cleanup + monthly quota reset.

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT" || exit 1

PHP_BIN="${PHP_BIN:-php}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    PHP_BIN="/usr/bin/php"
fi

"$PHP_BIN" artisan schedule:run >> storage/logs/cron.log 2>&1
