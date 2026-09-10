#!/usr/bin/env bash
set -eu
root="$(cd -- "$(dirname -- "$0")/.." && pwd)"
php "$root/points/sync.php"
php "$root/notifications/sync.php"
